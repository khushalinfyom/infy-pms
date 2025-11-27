<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->hiddenLabel()
                    ->formatStateUsing(function ($record) {
                        return 'Note: Cost is calculated based on salary.';
                    })
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'display: flex; justify-content: flex-end;'
                    ]),

                Section::make()
                    ->schema([
                        Group::make([
                            TextEntry::make('name')
                                ->hiddenLabel(),

                            TextEntry::make('date_range')
                                ->hiddenLabel()
                                ->getStateUsing(function ($record) {
                                    return $record->start_date->format('jS M Y') . ' - ' . $record->end_date->format('jS M Y');
                                })
                                ->extraAttributes([
                                    'style' => 'display: flex; justify-content: flex-end;'
                                ]),
                        ])
                            ->columns([
                                'default' => 2,
                                'md' => 2,
                            ]),

                        //all department name using section and department name in title

                        // TextEntry::make('departments')
                        //     ->hiddenLabel()
                        //     ->getStateUsing(function ($record) {
                        //         return $record->departments->pluck('name')->implode(', ');
                        //     }),

                        // RepeatableEntry::make('departments')
                        //     ->hiddenLabel()
                        //     ->schema(fn($record) => [
                        //         Section::make($record->departments->pluck('name') ?? 'Department')
                        //             ->schema([
                        //             ]),
                        //     ])


                        // TextEntry::make('departments')
                        //     ->hiddenLabel()
                        //     ->getStateUsing(function ($record) {
                        //         return $record->departments->pluck('name')->implode(', ');
                        //     })
                        //     ->extraAttributes([
                        //         'style' => 'display: flex; justify-content: flex-end;'
                        //     ]),


                    ])
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'display: flex;'
                    ]),


                // Section::make('Departments')
                //     ->schema([

                Group::make(function ($record) {
                    $departments = [];
                    $clients = [];

                    if ($record->departments && $record->departments->count() > 0) {
                        foreach ($record->departments as $department) {
                            $departments[] = Section::make('department' . $department->id)
                                ->heading($department->name)
                                ->collapsible()
                                ->schema([]);
                        }
                    }

                    return $departments;
                })
                    ->columnSpanFull(),


                RepeatableEntry::make('departments')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('name')
                            ->hiddenLabel(),


                        RepeatableEntry::make('clients')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('name')
                                    ->hiddenLabel(),
                            ])

                    ])
                    ->columnSpanFull()
                    ->visible(fn($record) => $record->departments->count() > 0),

                // ])
                // ->collapsed(),

                // Section::make('Clients')
                //     ->schema([
                //         RepeatableEntry::make('clients')
                //             ->schema([
                //                 TextEntry::make('name'),
                //             ])
                //             ->columnSpanFull()
                //             ->visible(fn($record) => $record->clients->count() > 0),
                //     ])
                //     ->collapsed(),

                // Section::make('Projects')
                //     ->schema([
                //         RepeatableEntry::make('projects')
                //             ->schema([
                //                 TextEntry::make('name')
                //                     ->formatStateUsing(fn($state, $record) => $state),
                //             ])
                //             ->columnSpanFull()
                //             ->visible(fn($record) => $record->projects->count() > 0),
                //     ])
                //     ->collapsed(),

                // Section::make('Users')
                //     ->schema([
                //         RepeatableEntry::make('users')
                //             ->schema([
                //                 TextEntry::make('name'),
                //             ])
                //             ->columnSpanFull()
                //             ->visible(fn($record) => $record->users->count() > 0),
                //     ])
                //     ->collapsed(),

                // Section::make('Meta')
                //     ->schema([
                //         TextEntry::make('meta')
                //             ->label('Meta Data')
                //             ->formatStateUsing(fn($state) => json_encode($state, JSON_PRETTY_PRINT)),
                //     ])
                //     ->collapsed(),
            ]);
    }
}
