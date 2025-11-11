<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\DeleteBulkAction;

class CustomDeleteBulkAction extends DeleteBulkAction
{
    public function setCommonProperties(string | Closure | null $url = null): static
    {
        return $this
            ->label('Delete Selected')
            ->modalSubmitActionLabel('Confirm')
            ->modalCancelActionLabel('Cancel')
            ->modalDescription('Are you sure you would like to do this?')
            ->successRedirectUrl(function ($table) use ($url) {
                try {
                    $getRecords = $table->getRecords();
                    $currentPage = $getRecords->currentPage();
                    $perPage = $getRecords->perPage();
                    $totalRecords = $getRecords->total();
                    $totalPages = ceil($totalRecords / $perPage);
                    if ($currentPage > $totalPages) {
                        return $url . '?page=' . $totalPages;
                    }
                } catch (\Throwable $th) {
                    return null;
                }
                return null;
            });
    }
}
