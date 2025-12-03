<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.users.expense_created_successfully');
    }

    public function getTitle(): string
    {
        return __('messages.users.create_expense');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }
}
