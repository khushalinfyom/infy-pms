<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Models\Report;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('id', 'desc')
            ->recordActionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(function ($livewire) {
                if (empty($livewire->tableSearch)) {
                    return __('messages.common.empty_table_heading', ['table' => 'reports']);
                } else {
                    return __('messages.common.empty_table_search_heading', ['table' => 'reports', 'search' => $livewire->tableSearch]);
                }
            })
            ->query(function () {
                $user = auth()->user();

                return Report::query()
                    ->when(
                        $user->hasRole('Admin'),
                        fn($query) => $query,
                        fn($query) => $query->where('owner_id', $user->id)
                    );
            })
            ->columns([

                TextColumn::make('name')
                    ->label(__('messages.common.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('messages.settings.start_date'))
                    ->date(),

                TextColumn::make('end_date')
                    ->label(__('messages.settings.end_date'))
                    ->date(),

                TextColumn::make('user.name')
                    ->label(__('messages.projects.created_by'))
                    ->placeholder('N/A'),
            ])
            ->filters([
                SelectFilter::make('created_by')
                    ->label(__('messages.projects.created_by'))
                    ->relationship('user', 'name')
                    ->placeholder(__('messages.users.all_users'))
                    ->native(false)
                    ->searchable()
                    ->preload()
            ])
            ->deferFilters(false)
            ->actions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip(__('messages.common.view')),

                EditAction::make()
                    ->iconButton()
                    ->tooltip(__('messages.common.edit')),

                \App\Filament\Actions\CustomDeleteAction::make()
                    ->setCommonProperties()
                    ->iconButton()
                    ->tooltip(__('messages.common.delete'))
                    ->modalHeading(__('messages.users.delete_report'))
                    ->successNotificationTitle(__('messages.users.report_deleted_successfully')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\CustomDeleteBulkAction::make()
                        ->setCommonProperties()
                        ->modalHeading(__('messages.users.delete_selected_reports'))
                        ->successNotificationTitle(__('messages.users.reports_deleted_successfully')),
                ]),
            ]);
    }
}
