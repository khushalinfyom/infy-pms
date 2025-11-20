<x-filament-panels::page>

    <div x-init="const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                $wire.loadMore();
            }
        });
    }, { rootMargin: '100px' });
    
    observer.observe($refs.loadMoreTrigger);">

        <div class="space-y-4">

            {{-- Activity List --}}
            @foreach ($this->activities as $activity)
                <div class="">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div
                                class="relative flex items-center justify-center w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900
                                after:content-[''] after:absolute after:left-1/2 after:top-full after:-translate-x-1/2
                                after:w-[2px] after:h-20 after:bg-primary-500">

                                <x-filament::icon :icon="$this->getActivityIcon($activity['subject_type'] ?? null)"
                                    class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>

                        <div class="flex-1 min-w-0 p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $activity['description'] ?? '' }}
                                    </p>

                                    <div
                                        class="flex items-center mt-1 space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                        @if (isset($activity['causer']) && $activity['causer'])
                                            <span>{{ $activity['causer']['name'] ?? 'Unknown User' }}</span>
                                        @endif
                                        <span>|</span>
                                        <span>{{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <div>
                                    <x-filament::badge :color="$this->getActivityColor($activity['description'] ?? '')">
                                        <span>{{ $activity['log_name'] ?? 'General' }}</span>
                                    </x-filament::badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Loader Skeletons - Livewire controlled --}}
            <div x-ref="loadMoreTrigger">

                <div wire:loading wire:target="loadMore" class="space-y-4 w-full">

                    {{-- Skeleton 1 --}}
                    <div class="w-full">
                        <div class="flex items-start space-x-3">
                            <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex-1 p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800 animate-pulse">
                                <div class="h-4 bg-gray-200 rounded w-3/4 dark:bg-gray-700"></div>
                                <div class="flex mt-2 space-x-2">
                                    <div class="h-3 bg-gray-200 rounded w-16 dark:bg-gray-700"></div>
                                    <div class="h-3 bg-gray-200 rounded w-16 dark:bg-gray-700"></div>
                                </div>
                                <div class="h-3 mt-2 bg-gray-200 rounded w-1/2 dark:bg-gray-700"></div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- No More Records --}}
                @if (!$this->hasMorePages && !$this->loading && count($this->activities) > 0)
                    <div class="text-sm text-center text-gray-500 py-4">
                        No more activities to load
                    </div>
                @endif
            </div>


            {{-- No activities --}}
            @if (count($this->activities) === 0 && !$this->loading)
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

</x-filament-panels::page>
