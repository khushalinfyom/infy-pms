<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label('New User')
                ->successNotificationTitle('User created successfully!')
                ->createAnother(false)
                ->modalWidth('lg')
                ->modalHeading('Create User')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['email_verified_at'] = Carbon::now();
                    $data['is_email_verified'] = 1;

                    if (isset($data['password'])) {
                        $data['set_password'] = 1;
                    }
                    return $data;
                })
                ->after(function ($record, array $data): void {
                    if (isset($data['role_id'])) {
                        $record->syncRoles([$data['role_id']]);
                    }

                    $projects = $record->projects()->pluck('projects.id')->toArray();

                    foreach ($projects as $projectId) {
                        $project = Project::find($projectId);

                        if ($project) {
                            activity()
                                ->causedBy(getLoggedInUser())
                                ->performedOn($project)
                                ->withProperties([
                                    'model' => User::class,
                                    'data'  => $project->name,
                                ])
                                ->useLog('User Assigned to Project')
                                ->log('Assigned ' . $record->name . ' to project');
                        }
                    }
                }),
        ];
    }
}
