<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->icon('heroicon-s-arrow-left')
                ->url(ProjectResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name . ' - ' . __('messages.projects.project_details');
    }

    public function deleteAttachment($mediaId)
    {
        $media = Media::find($mediaId);
        if ($media) {
            $media->delete();
            Notification::make()
                ->success()
                ->title(__('messages.projects.attachment_deleted_successfully'))
                ->send();
        }
    }
}
