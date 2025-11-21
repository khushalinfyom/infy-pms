<?php

namespace App\Filament\Pages;

use App\Enums\AdminPanelSidebar;
use App\Models\ProjectActivity;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ActivityLogs extends Page
{
    protected string $view = 'filament.pages.activity-logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = AdminPanelSidebar::ACTIVITY_LOGS->value;

    public int $perPage = 10;
    public int $page = 1;
    public bool $hasMorePages = true;
    public bool $loading = false;
    public array $activities = [];

    public static function getNavigationLabel(): string
    {
        return 'Activity Logs';
    }

    public function getTitle(): string | Htmlable
    {
        return 'Activity Logs';
    }

    public function mount(): void
    {
        $this->loadActivities();
    }

    public function loadActivities(): void
    {
        $this->loading = true;
        $this->dispatch('loading-started');

        $activities = ProjectActivity::with(['causer', 'subject'])
            ->latest()
            ->paginate($this->perPage, ['*'], 'page', $this->page);

        $this->hasMorePages = $activities->hasMorePages();

        if ($this->page === 1) {
            $this->activities = $activities->items();
        } else {
            $this->activities = array_merge($this->activities, $activities->items());
        }

        $this->loading = false;
        $this->dispatch('loading-finished');
    }

    public function loadMore(): void
    {
        sleep(10);
        if (!$this->hasMorePages) {
            return;
        }

        $this->page++;
        $this->loadActivities();
    }

    public function getActivityIcon(?string $modelType): string
    {
        if (!$modelType) {
            return 'heroicon-o-question-mark-circle';
        }

        switch ($modelType) {
            case 'App\Models\Department':
                return 'heroicon-o-building-office';
            case 'App\Models\Client':
                return 'heroicon-o-user-group';
            case 'App\Models\Role':
                return 'heroicon-o-shield-check';
            case 'App\Models\Project':
                return 'heroicon-o-folder';
            case 'App\Models\Task':
                return 'heroicon-o-clipboard-document-check';
            case 'App\Models\Report':
                return 'heroicon-o-document-text';
            case 'App\Models\Invoice':
                return 'heroicon-o-document-currency-dollar';
            case 'App\Models\Event':
                return 'heroicon-o-calendar-days';
            case 'App\Models\User':
                return 'heroicon-o-user';
            default:
                return 'heroicon-o-information-circle';
        }
    }

    public function getActivityColor(string $description): string
    {
        if (str_contains(strtolower($description), 'create') || str_contains(strtolower($description), 'new')) {
            return 'success';
        } elseif (str_contains(strtolower($description), 'update') || str_contains(strtolower($description), 'edit')) {
            return 'warning';
        } elseif (str_contains(strtolower($description), 'delete') || str_contains(strtolower($description), 'remove')) {
            return 'danger';
        } else {
            return 'primary';
        }
    }

    public function getSubjectTypeName(?string $subjectType): string
    {
        if (!$subjectType) {
            return 'N/A';
        }

        $parts = explode('\\', $subjectType);
        return end($parts);
    }
}
