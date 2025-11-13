<?php

namespace App\Filament\Resources\ArchivedUsers\Pages;

use App\Filament\Resources\ArchivedUsers\ArchivedUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageArchivedUsers extends ManageRecords
{
    protected static string $resource = ArchivedUserResource::class;
}
