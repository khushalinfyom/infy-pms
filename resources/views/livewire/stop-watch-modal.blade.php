<div style="height: 0px;">
    <x-filament::modal id="stop-watch-modal" slide-over>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-icon name="phosphor-timer" class="w-5 h-5" />
                <span>
                    @if ($activeTimeEntry)
                        Clock Out
                    @else
                        Clock In
                    @endif
                </span>
            </div>
        </x-slot>

        <div>
            @if ($activeTimeEntry)
                <!-- Show clock out view -->
                <div class="">
                    <div class="mb-4">
                        <p class="text-md font-semibold">My Timing</p>
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg mt-4"
                            wire:poll.1s="updateTimer">
                            <p
                                class="text-sm font-semibold text-green-400 dark:text-green-400 flex justify-center items-center">
                                Current Time:</p>
                            <p
                                class="text-gray-600 dark:text-gray-400 font-bold text-2xl flex justify-center items-center mt-2">
                                {{ gmdate('H:i:s', $elapsedTime) }}
                            </p>
                        </div>

                        <x-filament::button wire:click="clockOut" class="w-full mt-4" color="danger"
                            wire:loading.attr="disabled">
                            Clock Out
                        </x-filament::button>

                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 mt-4">
                            @if ($activeTimeEntry->task)
                                <p class="text-black dark:text-white font-bold mb-2">
                                    {{ $activeTimeEntry->task->title }}
                                </p>
                            @endif
                            @if ($activeTimeEntry->task->project)
                                <div class="flex items-center">
                                    @svg('phosphor-folder-open', ['class' => 'sm:w-5 sm:h-5 w-4 h-4 mr-2 text-gray-600 dark:text-gray-300'])
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{ $activeTimeEntry->task->project->name }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
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
