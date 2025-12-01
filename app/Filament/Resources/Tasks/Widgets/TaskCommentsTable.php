<?php

namespace App\Filament\Resources\Tasks\Widgets;

use App\Models\Comment;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskCommentsTable extends TableWidget
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
                    return __('messages.common.empty_table_heading', ['table' => 'comments']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'comments', 'search' => $livewire->tableSearch]);
                }
            })
            ->query(fn(): Builder => Comment::query()->where('task_id', $this->record->id))
            ->columns([
                TextColumn::make('comment')
                    ->label(__('messages.projects.comment'))
                    ->html()
                    ->wrap(),

                TextColumn::make('created_by')
                    ->label(__('messages.projects.created_by'))
                    ->getStateUsing(fn($record) => $record->createdUser->name),

                TextColumn::make('created_at')
                    ->label(__('messages.projects.created_on'))
                    ->getStateUsing(fn($record) => Carbon::parse($record->created_at)->diffForHumans()),
            ])
            ->headerActions([
                CreateAction::make('create_comment')
                    ->model(Comment::class)
                    ->icon('heroicon-s-plus')
                    ->label(__('messages.projects.new_comment'))
                    ->modalWidth('lg')
                    ->modalHeading(__('messages.projects.create_comment'))
                    ->form($this->createCommentForm())
                    ->createAnother(false)
                    ->successNotificationTitle(__('messages.projects.comment_created_successfully')),
            ])
            ->recordActions([
                EditAction::make('edit')
                    ->label('Edit')
                    ->iconButton()
                    ->tooltip(__('messages.common.edit'))
                    ->modalWidth('md')
                    ->modalHeading(__('messages.projects.edit_comment'))
                    ->form($this->createCommentForm())
                    ->successNotificationTitle(__('messages.projects.comment_updated_successfully')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.projects.delete_comment'))
                    ->successNotificationTitle(__('messages.projects.comment_deleted_successfully')),
            ]);
    }

    public function createCommentForm()
    {
        return [
            Hidden::make('task_id')
                ->default($this->record->id),

            Hidden::make('created_by')
                ->default(auth()->user()->id),

            RichEditor::make('comment')
                ->label(__('messages.projects.comment'))
                ->placeholder(__('messages.projects.comment'))
                ->columnSpanFull()
                ->extraAttributes(['style' => 'min-height: 200px;'])
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ])
                ->required(),
        ];
    }
}
