<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Models\Report;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('owner_id')
                    ->numeric(),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('end_date')
                    ->date(),
                TextEntry::make('report_type')
                    ->numeric(),
                TextEntry::make('report_data')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('invoice_generate')
                    ->boolean(),
                TextEntry::make('meta')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Report $record): bool => $record->trashed()),
            ]);
    }
}
