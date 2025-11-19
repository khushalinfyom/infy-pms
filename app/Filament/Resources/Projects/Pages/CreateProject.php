<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Create Project';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Project Created Successfully';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }

    // protected function afterCreate(): void
    // {
    //     activity()
    //         ->causedBy(getLoggedInUser())
    //         ->performedOn($this->record)
    //         ->withProperties([
    //             'model' => Project::class,
    //             'data'  => '',
    //         ])
    //         ->useLog('Project Created')
    //         ->log('Project ' . $this->record->name . ' created.');
    // }

    public function afterCreate()
    {
        activity()
            ->causedBy(getLoggedInUser())
            ->performedOn($this->record)
            ->withProperties([
                'model' => Project::class,
                'data'  => '',
            ])
            ->useLog('Project Created')
            ->log('Project ' . $this->record->name . ' created.');
    }
}
