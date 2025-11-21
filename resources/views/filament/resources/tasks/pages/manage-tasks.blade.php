<x-filament-panels::page>
    <div class="space-y-4">
        {{-- @dd($data) --}}
        @forelse($data as $task)
            <x-filament::section>
                <div class="rounded flex justify-between">
                    <span>{{ $task['title'] }}</span>
                    <span>{{ $task['project']['name'] ?? 'N/A' }}</span>
                    <span>
                        @if (!empty($task['project']['users']))
                            {{ collect($task['project']['users'])->pluck('name')->join(', ') }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </x-filament::section>

        @empty
            <li>No tasks found.</li>
        @endforelse
    </div>
</x-filament-panels::page>
