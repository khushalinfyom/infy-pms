<?php

namespace App\Filament\Resources\Departments;

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

    protected static ?string $recordTitleAttribute = 'Department';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('Name')
                    ->unique()
                    ->required(),

                ColorPicker::make('color')
                    ->label('Color')
                    ->placeholder('Color')
                    ->required(),

                RichEditor::make('description')
                    ->label('Description')
                    ->placeholder('Description')
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'height: 200px;']),
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
                    ->html(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel('Actions')
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
                    ->successNotificationTitle('Department updated successfully!'),

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
                    ->extraAttributes(['style' => 'height: 200px;']),
            ])
            ->action(function (array $data) use ($set, $inputName, $record) {
                if (isset($record) && $record) {
                    $record->update([
                        'name' => $data['name'],
                        'color' => $data['color'],
                        'description' => $data['description'],
                    ]);
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
