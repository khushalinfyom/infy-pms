<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\Taxes\TaxResource;
use App\Models\Tax;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class CreateInvoice extends Page implements HasForms
{
    use InteractsWithRecord, InteractsWithForms;

    protected static string $resource = ReportResource::class;

    protected string $view = 'filament.resources.reports.pages.create-invoice';

    public ?array $data = [];
    public array $clientsOptions = [];
    public array $projectsOptions = [];
    public array $reportTasks = [];
    public array $invoiceSummary = [];

    public function updated($name, $value): void
    {
        if (in_array($name, ['data.tax_id', 'data.discount_type', 'data.discount'])) {
            $this->invoiceSummary = $this->calculateInvoiceSummary();
        }
    }

    private function getReportFilters(): array
    {
        return [
            'departments' => $this->record->filters()
                ->where('param_type', \App\Models\Department::class)
                ->pluck('param_id')
                ->toArray(),

            'clients' => $this->record->filters()
                ->where('param_type', \App\Models\Client::class)
                ->pluck('param_id')
                ->toArray(),

            'projects' => $this->record->filters()
                ->where('param_type', \App\Models\Project::class)
                ->pluck('param_id')
                ->toArray(),

            'users' => $this->record->filters()
                ->where('param_type', \App\Models\User::class)
                ->pluck('param_id')
                ->toArray(),
        ];
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $clients  = $this->getFinalClientsFromReport();
        $projects = $this->getFinalProjectsFromReport();

        $this->clientsOptions = $clients->pluck('name', 'id')->toArray();
        $this->projectsOptions = $projects->pluck('name', 'id')->toArray();
        $this->reportTasks = $this->getReportInfolist();
        $this->invoiceSummary = $this->calculateInvoiceSummary();

        $this->form->fill([
            'name' => 'Invoice from ' . $this->record->start_date->format('Y-m-d') . ' to ' . $this->record->end_date->format('Y-m-d'),
            'client_id'  => $clients->pluck('id')->toArray(),
            'project_id' => $projects->pluck('id')->toArray(),
            'invoice_number' => generateUniqueInvoiceNumber(),
            'tax_id' => null,
            'discount_type' => '0',
            'discount' => 0,
            'issue_date' => now()->format('Y-m-d'),
        ]);
    }

    public function getFinalClientsFromReport()
    {
        $filters = $this->getReportFilters();

        $clients = \App\Models\Client::query()
            ->when(!empty($filters['clients']), fn($q) => $q->whereIn('id', $filters['clients']))
            ->when(!empty($filters['departments']), function ($q) use ($filters) {
                $q->whereHas('department', fn($d) => $d->whereIn('id', $filters['departments']));
            })
            ->get();

        return $clients->map(function ($client) use ($filters) {

            $totalMinutes = 0;

            foreach ($client->projects->whereIn('id', $filters['projects']) as $project) {

                $projectUsers = $project->users
                    ->when(!empty($filters['users']), fn($q) => $q->whereIn('id', $filters['users']));

                foreach ($projectUsers as $user) {

                    $minutes = \App\Models\Task::where('project_id', $project->id)
                        ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                        ->with('timeEntries')
                        ->get()
                        ->flatMap
                        ->timeEntries
                        ->sum('duration');

                    $totalMinutes += $minutes;
                }
            }

            $client->total_minutes = $totalMinutes;

            return $client;
        })->filter(fn($client) => $client->total_minutes > 0);
    }

    public function getFinalProjectsFromReport()
    {
        $filters = $this->getReportFilters();

        $projects = \App\Models\Project::query()
            ->when(!empty($filters['projects']), fn($q) => $q->whereIn('id', $filters['projects']))
            ->when(!empty($filters['clients']), fn($q) => $q->whereIn('client_id', $filters['clients']))
            ->when(!empty($filters['departments']), function ($q) use ($filters) {
                $q->whereHas('client.department', fn($d) => $d->whereIn('id', $filters['departments']));
            })
            ->get();

        return $projects->map(function ($project) use ($filters) {

            $totalMinutes = 0;

            $projectUsers = $project->users
                ->when(!empty($filters['users']), fn($q) => $q->whereIn('id', $filters['users']));

            foreach ($projectUsers as $user) {

                $minutes = \App\Models\Task::where('project_id', $project->id)
                    ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                    ->with('timeEntries')
                    ->get()
                    ->flatMap
                    ->timeEntries
                    ->sum('duration');

                $totalMinutes += $minutes;
            }

            $project->total_minutes = $totalMinutes;

            return $project;
        })->filter(fn($project) => $project->total_minutes > 0);
    }

    public function getTitle(): string
    {
        return __('messages.users.new_invoice');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->icon('heroicon-s-arrow-left')
                ->url(ReportResource::getUrl('view', ['record' => $this->record->id]))
                ->color('gray'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('saveAndSend')
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('saveAsDraft')
                ->label('Save as Draft')
                ->action('saveAsDraft')
                ->color('warning'),

            Action::make('saveAndSend')
                ->label('Save & Send')
                ->submit('saveAndSend')
                ->action('saveAndSend')
                ->color('success'),

            Action::make('cancel')
                ->label(__('messages.common.cancel'))
                ->url(ReportResource::getUrl('view', ['record' => $this->record->id]))
                ->color('gray')
                ->outlined(),
        ];
    }

    public function saveAsDraft(): void
    {
        $this->form->validate();

        $data = $this->data;
        $summary = $this->invoiceSummary;

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'name' => $data['name'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'discount' => (float)($data['discount'] ?? 0),
                'discount_type' => (int)($data['discount_type'] ?? 0),
                'tax_id' => $data['tax_id'] ?? null,
                'status' => Invoice::STATUS_DRAFT,
                'amount' => (float)($summary['total'] ?? 0),
                'sub_total' => (float)($summary['subtotal'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'total_hour' => (string)($summary['total_hours'] ?? 0),
            ]);

            // Attach clients and projects (pivot tables)
            $clientIds = $data['client_id'] ?? [];
            if (!empty($clientIds)) {
                $invoice->invoiceClients()->attach($clientIds);
            }

            $projectIds = $data['project_id'] ?? [];
            if (!empty($projectIds)) {
                $invoice->invoiceProjects()->attach($projectIds);
            }

            // Create invoice items for each task row
            foreach ($this->reportTasks as $row) {
                $hoursDecimal = 0;
                preg_match('/(\d+)\s*hr/', $row['duration'] ?? '', $hM);
                if (!empty($hM)) {
                    $hoursDecimal += (int)$hM[1];
                }
                preg_match('/(\d+)\s*min/', $row['duration'] ?? '', $mM);
                if (!empty($mM)) {
                    $hoursDecimal += ((int)$mM[1]) / 60;
                }

                $invoice->invoiceItems()->create([
                    'item_name' => $row['task'] ?? null,
                    'task_id' => $row['task_id'] ?? null,
                    'hours' => (string)round($hoursDecimal, 2),
                    'task_amount' => (float)($row['amount'] ?? 0),
                    'fix_rate' => ($row['budget_type'] ?? null) == \App\Models\Project::FIXED_COST ? (float)$row['amount'] : null,
                    'item_project_id' => $row['project_id'] ?? null,
                    'description' => $row['description'] ?? null,
                ]);
            }

            DB::commit();

            Notification::make()
                ->title('Invoice saved as draft successfully!')
                ->success()
                ->send();

            redirect(ReportResource::getUrl('index'));
            return;
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error saving invoice')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function saveAndSend(): void
    {
        $this->form->validate();

        $data = $this->data;
        $summary = $this->invoiceSummary;

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'name' => $data['name'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'discount' => (float)($data['discount'] ?? 0),
                'discount_type' => (int)($data['discount_type'] ?? 0),
                'tax_id' => $data['tax_id'] ?? null,
                'status' => Invoice::STATUS_SENT,
                'amount' => (float)($summary['total'] ?? 0),
                'sub_total' => (float)($summary['subtotal'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'total_hour' => (string)($summary['total_hours'] ?? 0),
            ]);

            // Attach clients and projects (pivot tables)
            $clientIds = $data['client_id'] ?? [];
            if (!empty($clientIds)) {
                $invoice->invoiceClients()->attach($clientIds);
            }

            $projectIds = $data['project_id'] ?? [];
            if (!empty($projectIds)) {
                $invoice->invoiceProjects()->attach($projectIds);
            }

            // Create invoice items for each task row
            foreach ($this->reportTasks as $row) {
                $hoursDecimal = 0;
                preg_match('/(\d+)\s*hr/', $row['duration'] ?? '', $hM);
                if (!empty($hM)) {
                    $hoursDecimal += (int)$hM[1];
                }
                preg_match('/(\d+)\s*min/', $row['duration'] ?? '', $mM);
                if (!empty($mM)) {
                    $hoursDecimal += ((int)$mM[1]) / 60;
                }

                $invoice->invoiceItems()->create([
                    'item_name' => $row['task'] ?? null,
                    'task_id' => $row['task_id'] ?? null,
                    'hours' => (string)round($hoursDecimal, 2),
                    'task_amount' => (float)($row['amount'] ?? 0),
                    'fix_rate' => ($row['budget_type'] ?? null) == \App\Models\Project::FIXED_COST ? (float)$row['amount'] : null,
                    'item_project_id' => $row['project_id'] ?? null,
                    'description' => $row['description'] ?? null,
                ]);
            }

            DB::commit();

            Notification::make()
                ->title('Invoice sent successfully!')
                ->success()
                ->send();

            redirect(ReportResource::getUrl('index'));
            return;
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error sending invoice')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.common.name'))
                            ->placeholder(__('messages.common.name'))
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->options(fn() => $this->clientsOptions)
                            ->multiple()
                            ->disabled()
                            ->default(fn() => array_keys($this->clientsOptions))
                            ->required(),

                        Select::make('project_id')
                            ->label('Project')
                            ->options(fn() => $this->projectsOptions)
                            ->multiple()
                            ->disabled()
                            ->default(fn() => array_keys($this->projectsOptions))
                            ->required(),

                        Select::make('tax_id')
                            ->label('Tax')
                            ->options(Tax::all()->pluck('name', 'id')->toArray())
                            ->live()
                            ->suffixAction(function (Set $set, Get $get) {
                                return TaxResource::getSuffixAction($set, 'tax_id');
                            })
                            ->native(false),

                        DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->default(now())
                            ->placeholder('Issue Date')
                            ->maxDate(now())
                            ->live()
                            ->native(false)
                            ->required(),

                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->placeholder('Due Date')
                            ->native(false)
                            ->minDate(now()->subDays(1))
                            ->live(),

                        TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->prefix('INV-')
                            ->disabled()
                            ->required(),

                        Select::make('discount_type')
                            ->label('Discount Type')
                            ->native(false)
                            ->live()
                            ->options([
                                '0' => 'No Discount',
                                '1' => 'Before Tax',
                                '2' => 'After Tax',
                            ])
                            ->required(),

                        TextInput::make('discount')
                            ->label('Discount (%)')
                            ->placeholder('0')
                            ->live()
                            ->default(0)
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function getReportInfolist(): array
    {
        $filters = $this->getReportFilters();
        $rows = [];

        $clients = \App\Models\Client::query()
            ->when(!empty($filters['clients']), fn($q) => $q->whereIn('id', $filters['clients']))
            ->when(!empty($filters['departments']), fn($q) => $q->whereHas('department', fn($d) => $d->whereIn('id', $filters['departments'])))
            ->get();

        foreach ($clients as $client) {
            foreach ($client->projects->whereIn('id', $filters['projects']) as $project) {
                foreach ($project->users->whereIn('id', $filters['users']) as $user) {

                    $tasks = \App\Models\Task::where('project_id', $project->id)
                        ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                        ->with('timeEntries')
                        ->get();

                    foreach ($tasks as $task) {
                        $minutes = $task->timeEntries->sum('duration');

                        if ($minutes <= 0) {
                            continue;
                        }

                        $hours = floor($minutes / 60);
                        $mins = $minutes % 60;

                        $duration =
                            $hours && $mins ? "{$hours} hr {$mins} min" : ($hours ? "{$hours} hr" : "{$mins} min");

                        $amount = 0;
                        $hours_decimal = $minutes / 60;

                        if ($project->budget_type == \App\Models\Project::FIXED_COST) {
                            $amount = $project->price;
                        } else {
                            $amount = $hours_decimal * $project->price;
                        }

                        $rows[] = [
                            'client'   => $client->name,
                            'project'  => $project->name,
                            'user'     => $user->name,
                            'task'     => $task->title,
                            'task_id'  => $task->id,
                            'project_id' => $project->id,
                            'duration' => $duration,
                            'amount'   => $amount,
                            'budget_type' => $project->budget_type,
                            'currency' => $project->currency,
                            'currency_symbol' => \App\Models\Project::getCurrencyClass($project->currency),
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    public function calculateInvoiceSummary(): array
    {
        $totalHours = 0;
        $subtotal = 0;
        $currencySymbol = '';
        $taxAmount = 0;
        $discountAmount = 0;
        $taxRate = 0;

        foreach ($this->reportTasks as $row) {
            $subtotal += $row['amount'];
            $currencySymbol = $row['currency_symbol'];

            preg_match('/(\d+)\s*hr/', $row['duration'], $matches);
            if (!empty($matches)) {
                $totalHours += (int) $matches[1];
            }

            preg_match('/(\d+)\s*min/', $row['duration'], $minMatches);
            if (!empty($minMatches)) {
                $totalHours += (int) $minMatches[1] / 60;
            }
        }

        $taxId = $this->data['tax_id'] ?? null;
        $discountType = (string)($this->data['discount_type'] ?? '0');
        $discountValue = (float)($this->data['discount'] ?? 0);

        $discountValue = min($discountValue, 100);

        if ($taxId) {
            $tax = Tax::find($taxId);
            $taxRate = $tax ? (float)$tax->tax : 0;
        }

        if ($discountType == '0' || $discountValue <= 0) {
            $discountAmount = 0;
        } elseif ($discountType == '1') {
            $discountAmount = ($subtotal * $discountValue) / 100;
        } elseif ($discountType == '2') {
            $discountAmount = 0;
        }

        $amountAfterDiscount = $subtotal - ($discountType == '1' ? $discountAmount : 0);

        if ($taxRate > 0) {
            $taxAmount = ($amountAfterDiscount * $taxRate) / 100;
        }

        if ($discountType == '2' && $discountValue > 0) {
            $discountAmount = (($subtotal + $taxAmount) * $discountValue) / 100;
        }

        $total = $subtotal + $taxAmount - $discountAmount;
        $total = max($total, 0);

        return [
            'total_hours' => round($totalHours, 2),
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'currency_symbol' => $currencySymbol,
        ];
    }
}
