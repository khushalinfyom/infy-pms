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
            ->recordActionsColumnLabel('Action')
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return 'No Comments found.';
                } else {
                    return 'No Comments found for "' . $livewire->tableSearch . '".';
                }
            })
            ->query(fn(): Builder => Comment::query()->where('task_id', $this->record->id))
            ->columns([
                TextColumn::make('comment')
                    ->label('Comment')
                    ->html()
                    ->wrap(),

                TextColumn::make('created_by')
                    ->label('Created By')
                    ->getStateUsing(fn($record) => $record->createdUser->name),

                TextColumn::make('created_at')
                    ->label('Created On')
                    ->getStateUsing(fn($record) => Carbon::parse($record->created_at)->diffForHumans()),
            ])
            ->headerActions([
                CreateAction::make('create_comment')
                    ->model(Comment::class)
                    ->icon('heroicon-s-plus')
                    ->label('New Comment')
                    ->modalWidth('lg')
                    ->modalHeading('Create Comment')
                    ->form($this->createCommentForm())
                    ->createAnother(false)
                    ->successNotificationTitle('Comment created successfully!'),
            ])
            ->recordActions([
                EditAction::make('edit')
                    ->label('Edit')
                    ->iconButton()
                    ->modalWidth('md')
                    ->modalHeading('Edit Comment')
                    ->form($this->createCommentForm())
                    ->successNotificationTitle('Comment updated successfully!'),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->modalHeading('Delete Comment')
                    ->successNotificationTitle('Comment deleted successfully!'),
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
                ->label('Comment')
                ->placeholder('Comment')
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
