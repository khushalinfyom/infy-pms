<?php

namespace App\Filament\Client\Resources\Projects;

use App\Enums\ClientPanelSidebar;
use App\Filament\Client\Resources\Projects\Pages\ListProjects;
use App\Filament\Client\Resources\Projects\Pages\ViewProject;
use App\Filament\Client\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Client\Resources\Projects\Schemas\ProjectInfolist;
use App\Filament\Client\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = ClientPanelSidebar::PROJECTS->value;

    protected static ?string $recordTitleAttribute = 'Project';

    public static function canViewAny(): bool
    {
        return authUserHasPermission('manage_projects');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'view' => ViewProject::route('/{record}'),
        ];
    }
}
