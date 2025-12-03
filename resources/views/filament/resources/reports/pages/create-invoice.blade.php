<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->content }}

        @include('filament.resources.reports.partials.task-table', ['rows' => $this->reportTasks])

        <div class="flex items-center justify-start gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">

            <x-filament::button color="warning" size="sm" wire:click="saveAsDraft">
                {{ __('Save as Draft') }}
            </x-filament::button>

            <x-filament::button color="primary" size="sm" wire:click="saveAndSend">
                {{ __('Save & Send') }}
            </x-filament::button>

            <x-filament::button color="grey" size="sm" tag="a"
                href="{{ \App\Filament\Resources\Reports\ReportResource::getUrl('view', ['record' => $this->record->id]) }}">
                {{ __('Cancel') }}
            </x-filament::button>

        </div>

    </div>
</x-filament-panels::page>
