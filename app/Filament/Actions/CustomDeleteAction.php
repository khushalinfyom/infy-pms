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
            ->label(__('messages.common.delete'))
            ->tooltip(__('messages.common.delete'))
            ->modalCancelActionLabel(__('messages.common.cancel'))
            ->modalSubmitActionLabel(__('messages.common.confirm'))
            ->modalDescription(__('messages.common.are_you_sure_you_would_like_to_do_this'))
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
