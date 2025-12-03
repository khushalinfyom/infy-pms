<x-filament-panels::page>
    <div class="space-y-4">
        @foreach ($this->getTasks() as $task)
            <div
                class="task-row bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between hover:shadow-md transition-all duration-300">

                {{-- LEFT SIDE --}}
                <div class="flex items-center space-x-6 overflow-hidden">

                    {{-- Status Dot & Title --}}
                    <div>
                        <div class="flex items-center space-x-2">

                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate w-56">
                                {{ $task->title }}
                            </h3>
                            {{-- Project Badge --}}
                            <span
                                class="mt-1 inline-block text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-blue-100">
                                {{ $task->project->name ?? 'No Project' }}
                            </span>
                        </div>

                    </div>

                    {{-- Time Tracking --}}
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                        <svg class="mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>

                        @php
                            $totalMinutes = $task->timeEntries->sum('duration');
                            $hours = floor($totalMinutes / 60);
                            $minutes = $totalMinutes % 60;
                        @endphp

                        <span>{{ sprintf('%02dh %02dm', $hours, $minutes) }}</span>
                    </div>

                    {{-- Assignees --}}
                    <div class="flex -space-x-2">
                        @foreach ($task->taskAssignee->take(4) as $assignee)
                            <img class="inline-block h-9 w-9 rounded-full border-2 border-white dark:border-gray-700"
                                src="{{ $assignee->getFirstMediaUrl('images') ?: 'https://ui-avatars.com/api/?background=random&name=' . urlencode($assignee->name) }}"
                                alt="{{ $assignee->name }}">
                        @endforeach

                        @if ($task->taskAssignee->count() > 4)
                            <span
                                class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 border-2 border-white dark:border-gray-700">
                                +{{ $task->taskAssignee->count() - 4 }}
                            </span>
                        @endif
                    </div>

                    {{-- Due Date --}}
                    <div>
                        @if ($task->due_date)
                            @php
                                $dueDate = \Carbon\Carbon::parse($task->due_date);
                                $isOverdue = $dueDate->isPast();
                            @endphp

                            <span
                                class="inline-flex items-center text-xs px-2 py-1 rounded-full
                            {{ $isOverdue
                                ? 'bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100'
                                : 'bg-green-100 text-green-700 dark:bg-green-700 dark:text-green-100' }}">
                                {{ $dueDate->format('M j, Y') }}
                            </span>
                        @else
                            <span
                                class="inline-flex items-center text-xs px-2 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-700 dark:text-red-100">
                                No Due Date
                            </span>
                        @endif
                    </div>
                </div>

                {{-- RIGHT SIDE ACTIONS --}}
                <div class="flex items-center space-x-3">

                    {{-- View --}}
                    {{-- <button type="button"
                        class="px-3 py-1.5 text-sm bg-indigo-50 text-indigo-700 dark:bg-indigo-700 dark:text-indigo-100 hover:bg-indigo-100 dark:hover:bg-indigo-600 rounded-md"
                        onclick="alert('Task details modal open')">
                        View
                    </button> --}}

                    {{-- Set Due Date --}}
                    {{-- @if (!$task->due_date)
                        <button type="button"
                            class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md"
                            onclick="promptSetDueDate({{ $task->id }})">
                            Set Due
                        </button>
                    @endif --}}

                    {{-- Complete --}}
                    {{-- <button type="button"
                        class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded-md"
                        onclick="confirmCompleteTask({{ $task->id }})">
                        Complete
                    </button> --}}
                </div>

            </div>
        @endforeach
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.on('taskUpdated', () => {
                // Reload the page to show updated data
                location.reload();
            });
        });

        function promptSetDueDate(taskId) {
            document.getElementById('taskIdInput').value = taskId;
            document.getElementById('dueDateModal').classList.remove('hidden');
            document.getElementById('dueDateModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('dueDateModal').classList.add('hidden');
            document.getElementById('dueDateModal').classList.remove('flex');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            document.getElementById('confirmModal').classList.remove('flex');
        }

        function confirmCompleteTask(taskId) {
            document.getElementById('completeTaskIdInput').value = taskId;
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirmModal').classList.add('flex');
        }

        function setDueDate() {
            const taskId = document.getElementById('taskIdInput').value;
            const dueDate = document.getElementById('dueDateInput').value;

            if (!dueDate) {
                alert('Please select a due date');
                return;
            }

            // Call Livewire method to set due date
            Livewire.dispatch('setTaskDueDate', {
                taskId: taskId,
                dueDate: dueDate
            });
            closeModal();
        }

        function completeTask() {
            const taskId = document.getElementById('completeTaskIdInput').value;

            // Call Livewire method to mark task as completed
            Livewire.dispatch('markTaskAsCompleted', {
                taskId: taskId
            });
            closeConfirmModal();
        }
    </script>
</x-filament-panels::page>
