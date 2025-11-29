<?php

namespace App\Filament\Resources\Departments;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Models\Department;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = AdminPanelSidebar::DEPARTMENTS->value;

    protected static ?string $recordTitleAttribute = 'Department';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('Department');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.users.departments');
    }

    public static function getLabel(): ?string
    {
        return __('messages.users.departments');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    TextInput::make('name')
                        ->label(__('messages.common.name'))
                        ->placeholder(__('messages.common.name'))
                        ->unique()
                        ->required()
                        ->columnSpan(3),

                    ColorPicker::make('color')
                        ->label(__('messages.common.color'))
                        ->placeholder(__('messages.common.color'))
                        ->required(),
                ])->columns(4)->columnSpanFull(),

                RichEditor::make('description')
                    ->label(__('messages.common.description'))
                    ->placeholder(__('messages.common.description'))
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'height: 200px;'])
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('messages.common.name')),

                TextEntry::make('description')
                    ->label(__('messages.common.description'))
                    ->html()
                    ->default('N/A'),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'Departments']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'Departments', 'search' => $livewire->tableSearch]);
                }
            })
            ->recordTitleAttribute('Department')
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.view_department'))
                    ->modalWidth('md'),

                EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton()
                    ->modalHeading(__('messages.users.edit_department'))
                    ->modalWidth('xl')
                    ->successNotificationTitle(__('messages.users.department_updated_successfully'))
                    ->mutateFormDataUsing(function (array $data): array {
                        if (trim(strip_tags($data['description'] ?? '')) === '') {
                            $data['description'] = null;
                        }
                        return $data;
                    })
                    ->after(function ($record) {
                        activity()
                            ->causedBy(getLoggedInUser())
                            ->performedOn($record)
                            ->withProperties([
                                'model' => Department::class,
                                'data'  => '',
                            ])
                            ->useLog('Department Updated')
                            ->log('Department updated');
                    }),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.users.delete_department'))
                    ->successNotificationTitle(__('messages.users.department_deleted_successfully')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.users.delete_selected_departments'))
                        ->successNotificationTitle(__('messages.users.departments_deleted_successfully')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDepartments::route('/'),
        ];
    }

    public static function getSuffixAction($set = null, $inputName = null, $recordId = null)
    {
        $record = null;
        if (!empty($recordId)) {
            $record = Department::find($recordId);
        }
        return Action::make('createDepartment')
            ->icon(function () use ($record) {
                if (isset($record) && $record) {
                    return 'heroicon-s-pencil-square';
                } else {
                    return 'heroicon-s-plus';
                }
            })
            ->modalWidth('4xl')
            ->label(function () use ($record) {
                if (isset($record) && $record) {
                    return __('messages.users.edit_department');
                } else {
                    return __('messages.users.new_department');
                }
            })
            ->modalHeading(function () use ($record) {
                if (isset($record) && $record) {
                    return __('messages.users.edit_department');
                } else {
                    return __('messages.users.create_department');
                }
            })
            ->form([

                Group::make([

                    TextInput::make('name')
                        ->label(__('messages.common.name'))
                        ->placeholder(__('messages.common.name'))
                        ->unique()
                        ->required(),

                    ColorPicker::make('color')
                        ->label(__('messages.common.color'))
                        ->placeholder(__('messages.common.color'))
                        ->required(),

                ])
                    ->columns(2),
                RichEditor::make('description')
                    ->label(__('messages.common.description'))
                    ->placeholder(__('messages.common.description'))
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'height: 200px;'])
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),
            ])
            ->action(function (array $data) use ($set, $inputName, $record) {
                if (isset($record) && $record) {
                    $record->update([
                        'name' => $data['name'],
                        'color' => $data['color'],
                        'description' => $data['description'],
                    ]);

                    activity()
                        ->causedBy(getLoggedInUser())
                        ->performedOn($record)
                        ->withProperties([
                            'model' => Department::class,
                            'data'  => '',
                        ])
                        ->useLog('Department Updated')
                        ->log('Department ' . $record->name . ' updated');

                    Notification::make()
                        ->title(__('messages.users.department_updated_successfully'))
                        ->success()
                        ->send();
                } else {
                    $record = Department::create([
                        'name' => $data['name'],
                        'color' => $data['color'],
                        'description' => $data['description'],
                    ]);

                    activity()
                        ->causedBy(getLoggedInUser())
                        ->performedOn($record)
                        ->withProperties([
                            'model' => Department::class,
                            'data'  => '',
                        ])
                        ->useLog('New Department Created')
                        ->log('New Department ' . $record->name . ' created');

                    Notification::make()
                        ->title(__('messages.users.department_created_successfully'))
                        ->success()
                        ->send();
                }
                if (!empty($set) && !empty($inputName)) {
                    $set($inputName, $record->id);
                }
            });
    }
}
