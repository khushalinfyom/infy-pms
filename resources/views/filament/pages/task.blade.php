<x-filament-panels::page>
    <div class="mx-auto w-full">
        <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="w-100">
                <x-filament::input.wrapper>
                    <x-filament::input wire:model.live.debounce.500ms="search" placeholder="Search tasks by title..."
                        type="search" />
                </x-filament::input.wrapper>
            </div>

            <!-- Filters using Filament Form -->
            <div class="flex items-center gap-3">
                {{ $this->form }}
            </div>
        </div>

        @forelse ($this->getTasks() as $task)
            <div
                class="flex justify-between items-start bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-3 hover:shadow-lg transition-shadow duration-200">

                <div class="flex flex-col flex-1 mr-5">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">
                        {{ $task->title }}
                    </h3>

                    <div class="flex items-center gap-1">
                        <x-filament::icon icon="heroicon-o-folder-open"
                            class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $task->project->name ?? 'No Project' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center">
                        @foreach ($task->taskAssignee->take(3) as $assignee)
                            <img src="{{ $assignee->getFirstMediaUrl('images') ?: 'https://ui-avatars.com/api/?background=random&name=' . urlencode($assignee->name) }}"
                                alt="{{ $assignee->name }}"
                                class="w-10 h-10 rounded-full -ml-2 first:ml-0 border-2 border-white dark:border-gray-800 shadow-sm">
                        @endforeach

                        @if ($task->taskAssignee->count() > 3)
                            <span class="text-xs text-gray-600 dark:text-gray-300 ml-2">
                                +{{ $task->taskAssignee->count() - 3 }}
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-col items-start">
                        @php
                            $totalMinutes = $task->timeEntries->sum('duration');
                            $hours = floor($totalMinutes / 60);
                            $minutes = $totalMinutes % 60;
                            $timeDisplay =
                                $hours > 0 ? $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '') : $minutes . 'm';
                        @endphp

                        <div class="flex gap-3">
                            <div class="flex gap-1">
                                <x-filament::icon icon="heroicon-o-clock"
                                    class="w-4 h-4 mb-2 mt-0.5
                                       text-gray-700 dark:text-gray-300" />

                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $timeDisplay }}
                                </p>
                            </div>

                            {{-- @if ($task->due_date)
                                <div class="flex gap-1">
                                    <x-filament::icon icon="heroicon-o-calendar"
                                        class="w-4 h-4 mb-2 mt-0.5
                                           text-gray-700 dark:text-gray-300" />
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                        {{ \Carbon\Carbon::parse($task->due_date)->format('d M, Y') }}
                                    </p>
                                </div>
                            @endif --}}
                        </div>

                        <div class="flex gap-3 items-center">

                            {{ $this->infoAction()->arguments(['task' => $task->id]) }}

                            @if (!$task->due_date)
                                {{ $this->dueDateAction()->arguments(['task' => $task->id]) }}
                            @endif

                            {{ $this->completeAction()->arguments(['task' => $task->id]) }}

                            {{ $this->editTaskAction()->arguments(['task' => $task->id]) }}

                            {{ $this->taskEntryAction()->arguments(['task' => $task->id]) }}

                            {{ $this->deleteTaskAction()->arguments(['task' => $task->id]) }}

                            {{ $this->viewDetailsAction->arguments(['task' => $task->id]) }}
                        </div>

                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 text-center">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass"
                    class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                    No tasks found
                </h3>
                <p class="mt-1 text-gray-500 dark:text-gray-400">
                    @if (!empty($this->search))
                        No tasks matched your search query "{{ $this->search }}".
                    @elseif (!is_null($this->project_id) || !is_null($this->user_id) || !is_null($this->status))
                        No tasks match your selected filters.
                    @else
                        You don't have any tasks assigned to you.
                    @endif
                </p>
            </div>
        @endforelse

        @if ($this->getTasks()->hasPages())
            <div class="mt-6">
                <x-filament::pagination :paginator="$this->getTasks()" :page-options="$this->getPerPageOptions()" :current-page-option-property="'perPage'" />
            </div>
        @endif
    </div>
</x-filament-panels::page>
