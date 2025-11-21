<?php

namespace App\Filament\Infolists\Components;

use App\Models\Project;
use Filament\Infolists\Components\Entry;

class ProjectActivityLogEntry extends Entry
{
    protected string $view = 'filament.infolists.components.project-activity-log-entry';

    /**
     * Load all activities for this project using only the project ID.
     */
    public function getActivities(int $projectId)
    {
        $project = Project::find($projectId);

        if (! $project) {
            return collect([]);
        }

        return $project->activities()
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($act) {
                return [
                    'id' => $act->id,
                    'description' => $act->description,
                    'subject_type' => $act->subject_type,
                    'log_name' => $act->log_name,
                    'created_at' => $act->created_at,
                    'causer' => $act->causer ? [
                        'id' => $act->causer->id,
                        'name' => $act->causer->name,
                    ] : null,
                ];
            });
    }

    /**
     * Icon based on model type.
     */
    public function getActivityIcon(?string $modelType): string
    {
        return match ($modelType) {
            'App\Models\Department' => 'heroicon-o-building-office',
            'App\Models\Client'     => 'heroicon-o-user-group',
            'App\Models\Role'       => 'heroicon-o-shield-check',
            'App\Models\Project'    => 'heroicon-o-folder',
            'App\Models\Task'       => 'heroicon-o-clipboard-document-check',
            'App\Models\Report'     => 'heroicon-o-document-text',
            'App\Models\Invoice'    => 'heroicon-o-document-currency-dollar',
            'App\Models\Event'      => 'heroicon-o-calendar-days',
            'App\Models\User'       => 'heroicon-o-user',
            default                 => 'heroicon-o-information-circle',
        };
    }

    /**
     * Badge color based on description keywords.
     */
    public function getActivityColor(string $description): string
    {
        $d = strtolower($description);

        return match (true) {
            str_contains($d, 'create'),
            str_contains($d, 'new')     => 'success',

            str_contains($d, 'update'),
            str_contains($d, 'edit')    => 'warning',

            str_contains($d, 'delete'),
            str_contains($d, 'remove')  => 'danger',

            default                     => 'primary',
        };
    }
}
