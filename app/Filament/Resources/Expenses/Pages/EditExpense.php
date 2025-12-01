<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->icon('heroicon-s-arrow-left')
                ->url(ExpenseResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return __('messages.users.edit_expense');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.users.expense_updated_successfully');
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl('index');
    }
}
