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
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No time entries found.';
                } else {
                    return 'No time entries found for "' . $livewire->tableSearch . '".';
                }
            })
            ->query(fn(): Builder => TimeEntry::query()->where('task_id', $this->record->id))
            ->columns([
                TextColumn::make('activityType.name')
                    ->label('Activity Type')
                    ->searchable(),

                TextColumn::make('start_time')
                    ->label('Start Time'),

                TextColumn::make('end_time')
                    ->label('End Time'),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(function ($state) {
                        $hours = floor($state / 60);
                        $minutes = $state % 60;
                        return "{$hours}:{$minutes} m";
                    }),

                TextColumn::make('entry_type')
                    ->label('Entry Type')
                    ->formatStateUsing(fn($state) => match ($state) {
                        1 => 'Stopwatch',
                        2 => 'Via Form',
                        default => 'N/A',
                    })
                    ->color(fn($state) => match ($state) {
                        1 => 'primary',
                        2 => 'success',
                        default => 'secondary',
                    })
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Created On')
                    ->getStateUsing(fn($record) => Carbon::parse($record->created_at)->format('d-M-Y')),
            ])
            ->filters([
                SelectFilter::make('activity_type_id')
                    ->label('Activity Type')
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
                    ->label('New Time Entry')
                    ->modalWidth('2xl')
                    ->modalHeading('Create Time Entry')
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
                    ->successNotificationTitle('Time Entry created successfully!'),
            ])
            ->recordActions([

                EditAction::make('edit')
                    ->label('Edit')
                    ->iconButton()
                    ->modalWidth('2xl')
                    ->modalHeading('Edit Time Entry')
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
                    ->successNotificationTitle('Time Entry updated successfully!'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Time Entry')
                    ->successNotificationTitle('Time Entry deleted successfully!')
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
                ->label('User')
                ->relationship('user', 'name')
                ->required()
                ->native(false)
                ->default(auth()->user()->id)
                ->disabled()
                ->columnSpanFull(),

            Group::make([
                Group::make([

                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('task.project', 'name')
                        ->required()
                        ->native(false)
                        ->default($this->record->project_id)
                        ->disabled()
                        ->afterStateHydrated(function (callable $set, callable $get) {
                            $set('project_id', $this->record->project_id);
                        }),

                    DateTimePicker::make('start_time')
                        ->label('Start Time')
                        ->placeholder('Start Time')
                        ->required()
                        ->native(false)
                        ->maxDate(now())
                        ->live()
                        ->afterStateUpdated(
                            fn($state, callable $set, callable $get) =>
                            $this->updateDuration($get, $set)
                        ),

                    DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->placeholder('End Time')
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
                        ->label('Duration (In Minutes)')
                        ->placeholder('Duration')
                        ->disabled()
                        ->required()
                        ->live(),

                ])
                    ->columns(1),

                Group::make([

                    Select::make('task_id')
                        ->label('Task')
                        ->relationship('task', 'title')
                        ->required()
                        ->native(false)
                        ->default($this->record->title)
                        ->disabled(),

                    Select::make('activity_type_id')
                        ->label('Activity Type')
                        ->relationship('activityType', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Textarea::make('note')
                        ->label('Note')
                        ->placeholder('Note')
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
