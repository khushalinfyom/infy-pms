<?php

namespace App\Filament\Client\Resources\Projects\Pages;

use App\Filament\Client\Resources\Projects\ProjectResource;
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
                ->label('Back')
                ->icon('heroicon-s-arrow-left')
                ->url(ProjectResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name . ' - Project Details';
    }

    public function deleteAttachment($mediaId)
    {
        $media = Media::find($mediaId);
        if ($media) {
            $media->delete();
            Notification::make()
                ->success()
                ->title('Attachment deleted successfully.')
                ->send();
        }
    }
}
