<?php

namespace App\Filament\Client\Resources\Invoices\Pages;

use App\Filament\Client\Resources\Invoices\InvoiceResource;
use App\Models\Tax;
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
use Exception;

class EditInvoice extends Page implements HasForms
{
    use InteractsWithRecord, InteractsWithForms;

    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.resources.invoices.pages.edit-invoice';

    public ?array $data = [];
    public array $clientsOptions = [];
    public array $projectsOptions = [];
    public array $invoiceItems = [];
    public array $invoiceSummary = [];

    public function updated($name, $value): void
    {
        if (in_array($name, ['data.tax_id', 'data.discount_type', 'data.discount'])) {
            $this->invoiceSummary = $this->calculateInvoiceSummary();
        }
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->clientsOptions = \App\Models\Client::pluck('name', 'id')->toArray();
        $this->projectsOptions = \App\Models\Project::pluck('name', 'id')->toArray();

        $clientIds = $this->record->invoiceClients->pluck('id')->toArray();
        $projectIds = $this->record->invoiceProjects->pluck('id')->toArray();

        $this->invoiceItems = $this->record->invoiceItems()
            ->with(['task.project'])
            ->get()
            ->map(function ($item) {
                $project = null;
                if ($item->task && $item->task->project) {
                    $project = $item->task->project;
                } elseif ($item->item_project_id) {
                    $project = \App\Models\Project::find($item->item_project_id);
                }

                return [
                    'item_name' => $item->item_name,
                    'task_id' => $item->task_id,
                    'project_id' => $item->item_project_id,
                    'hours' => $item->hours,
                    'amount' => $item->task_amount,
                    'description' => $item->description,
                    'budget_type' => $item->fix_rate ? 'fixed' : 'hourly',
                    'project_name' => $project ? $project->name : null,
                    'currency' => $project ? $project->currency : null,
                ];
            })
            ->toArray();

        $this->invoiceSummary = $this->calculateInvoiceSummaryFromDatabase();

        $this->form->fill([
            'name' => $this->record->name,
            'client_id' => $clientIds,
            'project_id' => $projectIds,
            'tax_id' => $this->record->tax_id,
            'issue_date' => $this->record->issue_date ? $this->record->issue_date->format('Y-m-d') : null,
            'due_date' => $this->record->due_date ? $this->record->due_date->format('Y-m-d') : null,
            'invoice_number' => $this->record->invoice_number,
            'discount_type' => (string)($this->record->discount_type ?? '0'),
            'discount' => $this->record->discount ?? 0,
            'notes' => $this->record->notes,
        ]);
    }

    public function getTitle(): string
    {
        return __('messages.users.edit_invoice');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->icon('heroicon-s-arrow-left')
                ->url(InvoiceResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save')
                ->action('save')
                ->color('success'),

            Action::make('cancel')
                ->label(__('messages.common.cancel'))
                ->url(InvoiceResource::getUrl('index'))
                ->color('gray')
                ->outlined(),
        ];
    }

    public function save(): void
    {
        $this->form->validate();

        $data = $this->data;

        DB::beginTransaction();
        try {
            $this->record->update([
                'name' => $data['name'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'discount' => (float)($data['discount'] ?? 0),
                'discount_type' => (int)($data['discount_type'] ?? 0),
                'tax_id' => $data['tax_id'] ?? null,
                'amount' => (float)($this->invoiceSummary['total'] ?? 0),
                'sub_total' => (float)($this->invoiceSummary['subtotal'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'total_hour' => (string)($this->invoiceSummary['total_hours'] ?? 0),
            ]);

            $clientIds = $data['client_id'] ?? [];
            $this->record->invoiceClients()->sync($clientIds);

            $projectIds = $data['project_id'] ?? [];
            $this->record->invoiceProjects()->sync($projectIds);

            DB::commit();

            Notification::make()
                ->title('Invoice updated successfully!')
                ->success()
                ->send();

            redirect(InvoiceResource::getUrl('index'));
            return;
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error updating invoice')
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
                            ->default(fn() => array_keys($this->clientsOptions))
                            ->required(),

                        Select::make('project_id')
                            ->label('Project')
                            ->options(fn() => $this->projectsOptions)
                            ->multiple()
                            ->default(fn() => array_keys($this->projectsOptions))
                            ->required(),

                        Select::make('tax_id')
                            ->label('Tax')
                            ->options(Tax::all()->pluck('name', 'id')->toArray())
                            ->live()
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

    public function calculateInvoiceSummaryFromDatabase(): array
    {
        $totalHours = 0;
        $subtotal = 0;
        $currencySymbol = '';
        $taxAmount = 0;
        $discountAmount = 0;
        $taxRate = 0;

        foreach ($this->invoiceItems as $row) {
            $subtotal += $row['amount'];

            if (isset($row['project_id'])) {
                $project = \App\Models\Project::find($row['project_id']);
                if ($project) {
                    $currencySymbol = \App\Models\Project::getCurrencyClass($project->currency);
                }
            }

            if (empty($currencySymbol)) {
                $currencySymbol = '$';
            }

            if (isset($row['hours'])) {
                $totalHours += (float)$row['hours'];
            }
        }

        $taxId = $this->record->tax_id;
        $discountType = (string)($this->record->discount_type ?? '0');
        $discountValue = (float)($this->record->discount ?? 0);

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

        $total = $this->record->amount ?? ($subtotal + $taxAmount - $discountAmount);
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

    public function calculateInvoiceSummary(): array
    {
        $totalHours = 0;
        $subtotal = 0;
        $currencySymbol = '';
        $taxAmount = 0;
        $discountAmount = 0;
        $taxRate = 0;

        foreach ($this->invoiceItems as $row) {
            $subtotal += $row['amount'];

            if (isset($row['project_id'])) {
                $project = \App\Models\Project::find($row['project_id']);
                if ($project) {
                    $currencySymbol = \App\Models\Project::getCurrencyClass($project->currency);
                }
            }

            if (empty($currencySymbol)) {
                $currencySymbol = '$';
            }

            if (isset($row['hours'])) {
                $totalHours += (float)$row['hours'];
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
