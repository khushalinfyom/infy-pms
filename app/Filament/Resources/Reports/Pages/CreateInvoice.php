<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateInvoice extends Page implements HasForms
{
    use InteractsWithRecord, InteractsWithForms;

    protected static string $resource = ReportResource::class;

    protected string $view = 'filament.resources.reports.pages.create-invoice';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // dd('Invoice from ' . $this->record->start_date->format('Y-m-d') . ' to ' . $this->record->end_date->format('Y-m-d'));
    }

    public function getTitle(): string
    {
        return 'New Invoice';
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('back')
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->url(ReportResource::getUrl('view', ['record' => $this->record->id]))
                ->color('gray'),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([

                        TextInput::make('name')
                            ->label('Name')
                            ->placeholder('Name')
                            ->default('Invoice from' . $this->record->start_date . 'to' . $this->record->end_date)
                            ->required(),

                        Select::make('project_id')
                            ->label('Project')
                            // ->relationship('projects', 'name')
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
