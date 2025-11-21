<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div {{ $getExtraAttributeBag() }}>

        @php
            $projectId = $getState();

            $activities = $entry->getActivities($projectId);
        @endphp

        <div class="space-y-6">

            @foreach ($activities as $activity)
                <div class="flex items-start space-x-4">

                    <div class="relative">
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center mt-2
                            bg-primary-100 dark:bg-primary-900">
                            <x-filament::icon :icon="$entry->getActivityIcon($activity['subject_type'])" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>

                    <div class="flex-1 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <div class="flex justify-between items-start">

                            <div>
                                <p class="text-sm font-medium font-semibold text-gray-900 dark:text-white">
                                    {{ $activity['log_name'] }}
                                </p>

                                <div class="flex items-center text-xs text-gray-600 dark:text-gray-500 mt-1 space-x-2">
                                    <span>{{ $activity['causer']['name'] ?? 'Unknown' }}
                                        {{ $activity['description'] ?? '' }}
                                    </span>
                                </div>
                            </div>

                            {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}

                        </div>
                    </div>

                </div>
            @endforeach

            @if ($activities->count() === 0)
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <x-filament::icon icon="heroicon-o-document-text" class="w-12 h-12 text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No activities found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        There are no activity logs to display yet.
                    </p>
                </div>
            @endif

        </div>

    </div>
</x-dynamic-component>
