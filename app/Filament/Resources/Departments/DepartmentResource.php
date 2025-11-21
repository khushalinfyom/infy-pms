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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    TextInput::make('name')
                        ->label('Name')
                        ->placeholder('Name')
                        ->unique()
                        ->required()
                        ->columnSpan(3),

                    ColorPicker::make('color')
                        ->label('Color')
                        ->placeholder('Color')
                        ->required(),
                ])->columns(4)->columnSpanFull(),

                RichEditor::make('description')
                    ->label('Description')
                    ->placeholder('Description')
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
                    ->label('Name'),

                TextEntry::make('description')
                    ->label('Description')
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
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No departments found.';
                } else {
                    return 'No departments found for "' . $livewire->tableSearch . '".';
                }
            })
            ->recordTitleAttribute('Department')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->tooltip('View')
                    ->iconButton()
                    ->modalHeading('View Department')
                    ->modalWidth('md'),

                EditAction::make()
                    ->tooltip('Edit')
                    ->iconButton()
                    ->modalHeading('Edit Department')
                    ->modalWidth('xl')
                    ->successNotificationTitle('Department updated successfully!')
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
                    ->tooltip('Delete')
                    ->modalHeading('Delete Department')
                    ->successNotificationTitle('Department deleted successfully!'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading('Delete Departments')
                        ->successNotificationTitle('Departments deleted successfully!'),
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
                    return 'Edit Department';
                } else {
                    return 'New Department';
                }
            })
            ->modalHeading(function () use ($record) {
                if (isset($record) && $record) {
                    return 'Edit Department';
                } else {
                    return 'Create Department';
                }
            })
            ->form([

                Group::make([

                    TextInput::make('name')
                        ->label('Name')
                        ->placeholder('Name')
                        ->unique()
                        ->required(),

                    ColorPicker::make('color')
                        ->label('Color')
                        ->placeholder('Color')
                        ->required(),

                ])
                    ->columns(2),
                RichEditor::make('description')
                    ->label('Description')
                    ->placeholder('Description')
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
                        ->title('Department updated successfully!')
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
                        ->title('Department created successfully!')
                        ->success()
                        ->send();
                }
                if (!empty($set) && !empty($inputName)) {
                    $set($inputName, $record->id);
                }
            });
    }
}
