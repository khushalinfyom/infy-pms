<?php

namespace App\Filament\Resources\Tasks;

use App\Enums\AdminPanelSidebar;
use App\Filament\Resources\Tasks\Pages\ManageTasks;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?int $navigationSort = AdminPanelSidebar::TASKS->value;

    protected static ?string $recordTitleAttribute = 'Task';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('title')
                    ->label('Title')
                    ->placeholder('Title')
                    ->required(),

                Select::make('project_id')
                    ->label('Project')
                    ->options(Project::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->reactive()
                    ->required(),

                Select::make('priority')
                    ->label('Priority')
                    ->options(Task::PRIORITY)
                    ->searchable(),

                Select::make('taskAssignee')
                    ->label('Assignee')
                    ->multiple()
                    ->options(function (callable $get) {
                        $projectId = $get('project_id');

                        if (!$projectId) {
                            return [];
                        }

                        return User::whereHas('projects', function ($q) use ($projectId) {
                            $q->where('project_id', $projectId);
                        })->pluck('name', 'id');
                    })
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->required(fn(callable $get) => !empty($get('project_id'))),

                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->placeholder('SelectDue Date')
                    ->native(false)
                    ->minDate(now()),

                TextInput::make('estimate_time')
                    ->label('Estimate Time')
                    ->reactive()
                    ->placeholder('Enter estimate')
                    ->default(0)
                    ->afterStateHydrated(function ($set, $get) {
                        if (! $get('estimate_time_type')) {
                            $set('estimate_time_type', Task::IN_HOURS);
                        }
                    })
                    ->extraInputAttributes(function ($get) {
                        return [
                            'type' => $get('estimate_time_type') === Task::IN_HOURS ? 'time' : 'number',
                            'min' => 0,
                        ];
                    })
                    ->suffixAction(
                        Action::make('set_hours')
                            ->button()
                            ->label('In Hours')
                            ->size('xs')
                            ->color(fn($get) => $get('estimate_time_type') === Task::IN_HOURS ? 'primary' : 'secondary')
                            ->action(function ($set) {
                                $set('estimate_time_type', Task::IN_HOURS);
                                $set('estimate_time', null);
                            })
                    )
                    ->suffixAction(
                        Action::make('set_days')
                            ->button()
                            ->label('In Days')
                            ->size('xs')
                            ->color(fn($get) => $get('estimate_time_type') === Task::IN_DAYS ? 'primary' : 'secondary')
                            ->action(function ($set) {
                                $set('estimate_time_type', Task::IN_DAYS);
                                $set('estimate_time', null);
                            })
                    ),

                Select::make('tags')
                    ->label('Tags')
                    ->multiple()
                    ->relationship('tags', 'name')
                    ->preload()
                    ->searchable()
                    ->native(false),

                RichEditor::make('description')
                    ->label('Description')
                    ->placeholder('Description')
                    ->columnSpanFull()
                    ->extraAttributes(['style' => 'min-height: 200px;'])
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
                TextEntry::make('priority')
                    ->placeholder('-'),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('project_id')
                    ->numeric(),
                TextEntry::make('status')
                    ->numeric(),
                TextEntry::make('due_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('completed_on')
                    ->date()
                    ->placeholder('-'),
                IconEntry::make('is_default')
                    ->boolean(),
                TextEntry::make('task_number')
                    ->numeric(),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('deleted_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(Task $record): bool => $record->trashed()),
                TextEntry::make('estimate_time')
                    ->placeholder('-'),
                TextEntry::make('estimate_time_type')
                    ->numeric()
                    ->placeholder('-'),
            ]);
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
                    return 'No tasks found.';
                } else {
                    return 'No tasks found for "' . $livewire->tableSearch . '".';
                }
            })
            ->recordTitleAttribute('Task')
            ->columns([
                TextColumn::make('priority')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('project_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_on')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_default')
                    ->boolean(),
                TextColumn::make('task_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deleted_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('estimate_time')
                    ->searchable(),
                TextColumn::make('estimate_time_type')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
