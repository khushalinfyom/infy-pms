<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportResource;
use App\Models\Client;
use App\Models\Department;
use App\Models\Project;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Create Report';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Report Created Successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }

    protected function afterCreate(): void
    {
        $report = $this->record;
        $data = $this->form->getRawState();

        $meta = $this->prepareReportMeta($data);
        $report->update(['meta' => $meta]);

        if (!empty($data['department_id'])) {
            $report->filters()->create([
                'param_type' => Department::class,
                'param_id' => $data['department_id'],
            ]);
        }

        if (!empty($data['client_id'])) {
            $report->filters()->create([
                'param_type' => Client::class,
                'param_id' => $data['client_id'],
            ]);
        }

        foreach ($data['project_ids'] ?? [] as $projectId) {
            $report->filters()->create([
                'param_type' => Project::class,
                'param_id' => $projectId,
            ]);
        }

        foreach ($data['user_ids'] ?? [] as $userId) {
            $report->filters()->create([
                'param_type' => User::class,
                'param_id' => $userId,
            ]);
        }

        foreach ($data['tag_ids'] ?? [] as $tagId) {
            $report->filters()->create([
                'param_type' => Tag::class,
                'param_id' => $tagId,
            ]);
        }

        foreach ($report->projects as $project) {
            activity()
                ->causedBy(getLoggedInUser())
                ->performedOn($project)
                ->withProperties([
                    'model' => Report::class,
                    'data'  => 'of ' . $project->name,
                ])
                ->useLog('Report Created')
                ->log('Created project report');
        }
    }

    protected function prepareReportMeta(array $data): array
    {
        return [
            'all_departments' => empty($data['department_id']),
            'all_clients'     => empty($data['client_id']),
            'all_projects'    => empty($data['project_ids']),
            'all_users'       => empty($data['user_ids']),
        ];
    }
}
