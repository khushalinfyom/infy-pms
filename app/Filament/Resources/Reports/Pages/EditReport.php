<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportResource;
use App\Models\Client;
use App\Models\Department;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->icon('heroicon-s-arrow-left')
                ->url(ReportResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return __('messages.users.edit_report');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.users.report_updated_successfully');
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl('index');
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        $record->filters()->whereIn('param_type', [
            Project::class,
            Tag::class,
            User::class,
            Department::class,
            Client::class,
        ])->delete();

        if (!empty($this->data['department_id'])) {
            $record->filters()->create([
                'param_type' => Department::class,
                'param_id'   => $this->data['department_id'],
            ]);
        }

        if (!empty($this->data['client_id'])) {
            $record->filters()->create([
                'param_type' => Client::class,
                'param_id'   => $this->data['client_id'],
            ]);
        }

        foreach ($this->data['project_ids'] ?? [] as $id) {
            $record->filters()->create([
                'param_type' => Project::class,
                'param_id'   => $id,
            ]);
        }

        foreach ($this->data['user_ids'] ?? [] as $id) {
            $record->filters()->create([
                'param_type' => User::class,
                'param_id'   => $id,
            ]);
        }

        foreach ($this->data['tag_ids'] ?? [] as $id) {
            $record->filters()->create([
                'param_type' => Tag::class,
                'param_id'   => $id,
            ]);
        }
    }
}
