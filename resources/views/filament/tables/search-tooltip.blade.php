@php
    $table = $this;
    $allTables = $allTables;

    foreach ($allTables as $table) {
        $searchableLabels = collect($this->table?->getColumns())
            ->filter(function ($column) {
                return $column?->isSearchable() ?? false;
            })
            ->map(function ($column) {
                return $column?->getLabel();
            })
            ->values();
    }
@endphp
<span
    class="relative inline-block cursor-help text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
    x-tooltip="{
            content: '{{ 'Search by' . ' ' . $searchableLabels->implode(', ') }}.',
            theme: {
                tooltip: 'bg-gray-800 text-white text-sm px-3 py-2 rounded shadow-lg',
                arrow: 'bg-gray-800'
            },
            placement: 'left',
        }">
    <x-filament::icon-button icon="heroicon-m-question-mark-circle" color="gray" label="New label" />
</span>
