<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Setting;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class InvoiceTemplate extends Page
{
    protected string $view = 'filament.clusters.settings.pages.invoice-template';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 2;

    public $selectedTemplate = 'defaultTemplate';
    public $invoiceColor = '#000000';

    public static function getNavigationLabel(): string
    {
        return __('messages.settings.invoice_template');
    }

    public function getHeading(): string|Htmlable
    {
        return __('messages.settings.invoice_template');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settingsKeys = [
            'default_invoice_template',
            'default_invoice_color',
        ];

        $settings = Setting::whereIn('key', $settingsKeys)->get()->pluck('value', 'key')->toArray();

        $this->form->fill($settings);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->key('form-actions'),
                    ])
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('messages.common.save'))
                ->submit('save')
                ->action('save'),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->model(Setting::where('key', 'default_invoice_template')->first())
            ->schema([
                Section::make([

                    Group::make()
                        ->schema([

                            Select::make('default_invoice_template')
                                ->options(Setting::INVOICE__TEMPLATE_ARRAY)
                                ->label(__('messages.settings.invoice_template'))
                                ->native(false)
                                ->extraAttributes([
                                    'style' => 'width: 300px;',
                                ])
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state == null) {
                                        return;
                                    }

                                    $this->selectedTemplate = $state;
                                    $this->invoiceColor = Setting::where('key', $state)->first()->template_color ?? '#e02121ff';
                                    $this->dispatch('updateColorPicker', color: $this->invoiceColor);
                                })
                                ->searchable(),

                            ColorPicker::make('default_invoice_color')
                                ->label(__('messages.settings.invoice_color'))
                                ->placeholder(__('messages.settings.invoice_color'))
                                ->required()
                                ->live(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),

                    ViewField::make('')
                        ->live()
                        ->view('invoices.components.invoice-template-preview')
                        ->viewData(fn(Get $get) => [
                            'data' => $get(),
                            'invoiceTemplate' => $get('default_invoice_template') ?? 'defaultTemplate',
                            'invColor' => $get('default_invoice_color') ?? '#000000',
                        ])
                        ->columnSpanFull()
                        ->extraAttributes([
                            'class' => 'w-full',
                            'style' => 'padding:0;',
                        ]),

                ])->columns(2)
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        try {

            $AppSetting = Setting::where('key', 'default_invoice_template')->first();

            $paymentSettings = [
                'default_invoice_template',
                'default_invoice_color',
            ];

            foreach ($paymentSettings as $key) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $data[$key] ?? null]
                );
            }

            Notification::make()
                ->success()
                ->title(__('messages.settings.invoice_template_updated_successfully'))
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();
        }
    }
}
