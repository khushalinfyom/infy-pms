<div style="height: 0px;">
    <x-filament::modal id="stop-watch-modal" slide-over>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-icon name="phosphor-timer" class="w-5 h-5" />
                <span>
                    @if($activeTimeEntry)
                        Clock Out
                    @else
                        Clock In
                    @endif
                </span>
            </div>
        </x-slot>

        <div>
            @if($activeTimeEntry)
                <!-- Show clock out view -->
                <div class="p-4">
                    <div class="mb-4">
                        <p class="text-md font-semibold">My Timing</p>
                        <div class="">
                            <p class="text-md">Current Time:</p>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($activeTimeEntry->start_time)->format('H:i:s') }}
                            </p>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Started at: {{ \Carbon\Carbon::parse($activeTimeEntry->start_time)->format('Y-m-d H:i:s') }}
                        </p>
                        @if($activeTimeEntry->task)
                            <p class="text-gray-600 dark:text-gray-400">
                                Task: {{ $activeTimeEntry->task->title }}
                            </p>
                        @endif
                        @if($activeTimeEntry->project)
                            <p class="text-gray-600 dark:text-gray-400">
                                Project: {{ $activeTimeEntry->project->name }}
                            </p>
                        @endif
                    </div>

                    <x-filament::button
                        wire:click="clockOut"
                        class="w-full"
                        color="danger"
                        wire:loading.attr="disabled"
                    >
                        Clock Out
                    </x-filament::button>
                </div>
            @else
                <!-- Show clock in form -->
                <form wire:submit="save">
                    {{ $this->form }}
                    <br>
                    <x-filament::button type="submit" class="w-full" color="success">Clock In</x-filament::button>
                </form>
            @endif
        </div>
    </x-filament::modal>
</div>
