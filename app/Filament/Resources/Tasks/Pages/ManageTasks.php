<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageTasks extends ManageRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([

                CreateAction::make()
                    ->label('New Task')
                    ->modalWidth('2xl')
                    ->icon('heroicon-s-plus')
                    ->modalHeading('Create Task')
                    ->createAnother(false)
                    ->using(function (array $data) {
                        if (!isset($data['estimate_time_type'])) {
                            $data['estimate_time_type'] = Task::IN_HOURS;
                        }

                        if ($data['estimate_time_type'] == Task::IN_HOURS) {
                            $data['estimate_time'] = $data['estimate_time'] ?? '00:00';
                        } else {
                            $data['estimate_time'] = is_numeric($data['estimate_time']) ? $data['estimate_time'] : '0';
                        }

                        if (empty($data['task_number']) && isset($data['project_id'])) {
                            $data['task_number'] = Task::generateUniqueTaskNumber($data['project_id']);
                        }

                        $data['created_by'] = Auth::id();

                        return Task::create($data);
                    })
                    ->after(function ($record, $data) {
                        if (! empty($data['taskAssignee'])) {
                            foreach ($data['taskAssignee'] as $userId) {
                                DB::table('task_assignees')->insert([
                                    'task_id' => $record->id,
                                    'user_id' => $userId,
                                ]);
                            }
                        }
                    })
                    ->successNotificationTitle('Task created successfully!'),

                Action::make('new time entry')
                    ->label('New Time Entry')
                    ->icon('heroicon-s-clock')
                    ->modalWidth('2xl')
                    ->modalHeading('Create Time Entry')
                    ->successNotificationTitle('Time Entry created successfully!'),

                Action::make('copy today activity')
                    ->label('Copy Today Activity')
                    ->icon('heroicon-s-document-duplicate')
                    ->successNotificationTitle('Today Activity copied successfully!'),
            ])
                ->label('Actions')
                ->button(),
        ];
    }
}
