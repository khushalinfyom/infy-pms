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
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No attachments found.';
                } else {
                    return 'No attachments found for "' . $livewire->tableSearch . '".';
                }
            })
            ->query(fn(): Builder => TaskAttachment::query()->where('task_id', $this->record->id))
            ->columns([
                SpatieMediaLibraryImageColumn::make('file_path')
                    ->collection('attachments')
                    ->label('Attachment')
                    ->circular(),
            ])
            ->headerActions([
                CreateAction::make('create_attachment')
                    ->model(TaskAttachment::class)
                    ->icon('heroicon-s-plus')
                    ->label('New Attachment')
                    ->modalWidth('md')
                    ->modalHeading('Create Attachment')
                    ->form($this->createAttachmentForm())
                    ->createAnother(false)
                    ->successNotificationTitle('Attachment created successfully!'),
            ])
            ->recordActions([
                EditAction::make('edit')
                    ->label('Edit')
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading('Edit Attachment')
                    ->form($this->createAttachmentForm())
                    ->successNotificationTitle('Attachment updated successfully!'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Attachment')
                    ->successNotificationTitle('Attachment deleted successfully!'),
            ]);
    }

    public function createAttachmentForm()
    {
        return [
            Hidden::make('task_id')
                ->default($this->record->id),

            SpatieMediaLibraryFileUpload::make('file')
                ->label('Attachment')
                ->disk(config('app.media_disk'))
                ->collection('attachments'),
        ];
    }
}
