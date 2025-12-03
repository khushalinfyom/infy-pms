<?php

namespace App\Filament\Resources\Tasks\Widgets;

use App\Models\ActivityType;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskTimeEntryTable extends TableWidget
{
    protected static ?string $heading = '';

    public ?Model $record = null;

    public function table(Table $table): Table
    {
        return $table->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'time entries']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'time entries', 'search' => $livewire->tableSearch]);
                }
            })
            ->query(fn(): Builder => TimeEntry::query()->where('task_id', $this->record->id))
            ->columns([
                TextColumn::make('activityType.name')
                    ->label(__('messages.settings.activity_type'))
                    ->searchable(),

                TextColumn::make('start_time')
                    ->label(__('messages.settings.start_time')),

                TextColumn::make('end_time')
                    ->label(__('messages.settings.end_time')),

                TextColumn::make('duration')
                    ->label(__('messages.settings.duration'))
                    ->formatStateUsing(function ($state) {
                        $hours = floor($state / 60);
                        $minutes = $state % 60;
                        return "{$hours}:{$minutes} m";
                    }),

                TextColumn::make('entry_type')
                    ->label(__('messages.settings.entry_type'))
                    ->formatStateUsing(fn($state) => match ($state) {
                        1 => __('messages.settings.stopwatch'),
                        2 => __('messages.settings.via_form'),
                        default => 'N/A',
                    })
                    ->color(fn($state) => match ($state) {
                        1 => 'primary',
                        2 => 'success',
                        default => 'secondary',
                    })
                    ->badge(),

                TextColumn::make('created_at')
                    ->label(__('messages.projects.created_on'))
                    ->getStateUsing(fn($record) => Carbon::parse($record->created_at)->format('d-M-Y')),
            ])
            ->filters([
                SelectFilter::make('activity_type_id')
                    ->label(__('messages.settings.activity_type'))
                    ->relationship('activityType', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),

            ])
            ->deferFilters(false)
            ->headerActions([
                CreateAction::make('create_comment')
                    ->model(TimeEntry::class)
                    ->icon('heroicon-s-plus')
                    ->label(__('messages.projects.new_time_entry'))
                    ->modalWidth('2xl')
                    ->modalHeading(__('messages.projects.create_time_entry'))
                    ->form($this->createTimeEntryForm())
                    ->createAnother(false)
                    ->using(function (array $data) {
                        if (!isset($data['duration']) || empty($data['duration'])) {
                            $start = Carbon::parse($data['start_time']);
                            $end = Carbon::parse($data['end_time']);
                            $seconds = $start->diffInSeconds($end);
                            $minutes = round($seconds / 60, 2);
                            $data['duration'] = $minutes;
                        }

                        return TimeEntry::create($data);
                    })
                    ->visible(authUserHasPermission('manage_time_entries'))
                    ->successNotificationTitle(__('messages.projects.time_entry_created_successfully')),
            ])
            ->recordActions([

                EditAction::make('edit')
                    ->label(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('2xl')
                    ->modalHeading(__('messages.projects.edit_time_entry'))
                    ->form($this->createTimeEntryForm())
                    ->using(function ($record, array $data) {
                        if (!isset($data['duration']) || empty($data['duration'])) {
                            $start = Carbon::parse($data['start_time']);
                            $end = Carbon::parse($data['end_time']);
                            $seconds = $start->diffInSeconds($end);
                            $minutes = round($seconds / 60, 2);
                            $data['duration'] = $minutes;
                        }

                        $record->update($data);

                        return $record;
                    })
                    ->successNotificationTitle(__('messages.projects.time_entry_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.projects.delete_time_entry'))
                    ->successNotificationTitle(__('messages.projects.time_entry_deleted_successfully'))
                    ->before(function ($record) {
                        $record->update([
                            'deleted_by' => auth()->id(),
                        ]);
                    }),
            ]);
    }

    public function createTimeEntryForm()
    {
        return [

            Hidden::make('entry_type')
                ->default(TimeEntry::VIA_FORM),

            Hidden::make('task_id')
                ->default($this->record->id),

            Hidden::make('user_id')
                ->default(auth()->user()->id),

            Select::make('user_id')
                ->label(__('messages.users.user'))
                ->relationship('user', 'name')
                ->required()
                ->native(false)
                ->default(auth()->user()->id)
                ->disabled()
                ->columnSpanFull(),

            Group::make([
                Group::make([

                    Select::make('project_id')
                        ->label(__('messages.projects.project'))
                        ->relationship('task.project', 'name')
                        ->required()
                        ->native(false)
                        ->default($this->record->project_id)
                        ->disabled()
                        ->afterStateHydrated(function (callable $set, callable $get) {
                            $set('project_id', $this->record->project_id);
                        }),

                    DateTimePicker::make('start_time')
                        ->label(__('messages.settings.start_time'))
                        ->placeholder(__('messages.settings.start_time'))
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->live()
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            $this->updateDuration($get, $set)
                        ),

                    DateTimePicker::make('end_time')
                        ->label(__('messages.settings.end_time'))
                        ->placeholder(__('messages.settings.end_time'))
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->minDate($this->record->start_time)
                        ->live()
                        ->default(now())
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            $this->updateDuration($get, $set)
                        ),

                    TextInput::make('duration')
                        ->label(__('messages.settings.duration_in_minutes'))
                        ->placeholder(__('messages.settings.duration'))
                        ->disabled()
                        ->required()
                        ->live(),

                ])
                    ->columns(1),

                Group::make([

                    Select::make('task_id')
                        ->label(__('messages.projects.task'))
                        ->relationship('task', 'title')
                        ->required()
                        ->native(false)
                        ->default($this->record->title)
                        ->disabled(),

                    Select::make('activity_type_id')
                        ->label(__('messages.settings.activity_type'))
                        ->relationship('activityType', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Textarea::make('note')
                        ->label(__('messages.settings.note'))
                        ->placeholder(__('messages.settings.note'))
                        ->required()
                        ->maxLength(255),

                ])
                    ->columns(1),

            ])
                ->columns(2),
        ];
    }

    protected function updateDuration(callable $get, callable $set)
    {
        $start = $get('start_time');
        $end = $get('end_time');

        if ($start && $end) {
            $startTime = Carbon::parse($start);
            $endTime = Carbon::parse($end);

            $seconds = $startTime->diffInSeconds($endTime);
            $minutes = round($seconds / 60, 2);

            $set('duration', $minutes);
        } else {
            $set('duration', 0);
        }
    }
}
