<?php

namespace App\Filament\Actions;

use Filament\Actions\DeleteAction;
use Closure;

class CustomDeleteAction extends DeleteAction
{
    public function setCommonProperties(string | Closure | null $url = null): static
    {
        return $this
            ->iconButton()
            ->label('Delete')
            ->tooltip('Delete')
            ->modalCancelActionLabel('Cancel')
            ->modalSubmitActionLabel('Confirm')
            ->modalDescription('Are you sure you would like to do this?')
            ->successRedirectUrl(function ($table, $action) use ($url) {
                $action->getLivewire()->deselectAllTableRecords();
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
