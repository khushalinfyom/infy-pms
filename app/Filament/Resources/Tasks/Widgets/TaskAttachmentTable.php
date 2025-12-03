<?php

namespace App\Filament\Resources\Tasks\Widgets;

use App\Models\TaskAttachment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskAttachmentTable extends TableWidget
{
    protected static ?string $heading = '';

    public ?Model $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'attachments']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'attachments', 'search' => $livewire->tableSearch]);
                }
            })
            ->query(fn(): Builder => TaskAttachment::query()->where('task_id', $this->record->id))
            ->columns([
                SpatieMediaLibraryImageColumn::make('file_path')
                    ->collection('attachments')
                    ->label(__('messages.projects.attachment'))
                    ->circular(),
            ])
            ->headerActions([
                CreateAction::make('create_attachment')
                    ->model(TaskAttachment::class)
                    ->icon('heroicon-s-plus')
                    ->label(__('messages.projects.new_attachment'))
                    ->modalWidth('md')
                    ->modalHeading(__('messages.projects.create_attachment'))
                    ->form($this->createAttachmentForm())
                    ->createAnother(false)
                    ->successNotificationTitle(__('messages.projects.attachment_created_successfully')),
            ])
            ->recordActions([
                EditAction::make('edit')
                    ->label(__('messages.common.edit'))
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading(__('messages.projects.edit_attachment'))
                    ->form($this->createAttachmentForm())
                    ->successNotificationTitle(__('messages.projects.attachment_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip( __('messages.common.delete'))
                    ->modalHeading( __('messages.projects.delete_attachment'))
                    ->successNotificationTitle( __('messages.projects.attachment_deleted_successfully')),
            ]);
    }

    public function createAttachmentForm()
    {
        return [
            Hidden::make('task_id')
                ->default($this->record->id),

            SpatieMediaLibraryFileUpload::make('file')
                ->label( __('messages.projects.attachment'))
                ->disk(config('app.media_disk'))
                ->collection('attachments'),
        ];
    }
}
