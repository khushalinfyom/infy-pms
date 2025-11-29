<x-filament-panels::page>

    <style>
        .tree-row {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s, color 0.2s;
        }

        .tree-left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .tree-children {
            margin-left: 22px;
            margin-top: 4px;
            display: none;
        }

        .chevron {
            transition: transform 0.2s ease-in-out;
        }

        .chevron.open {
            transform: rotate(90deg);
        }

        .light-department {
            border: 1px solid #0055ff;
            margin-top: 30px;
        }

        .light-client {
            border: 1px solid #00aaff;
        }

        .light-project {
            border: 1px solid #ff9100;
        }

        .light-user {
            border: 1px solid #00b33f;
        }

        .light-task {
            border: 1px solid #ff3086;
            margin-left: 15px;
        }

        .dark .dark-department {
            border: 1px solid #a1c0ff;
            margin-top: 30px;
        }

        .dark .dark-client {
            border: 1px solid #b4e6ff;
        }

        .dark .dark-project {
            border: 1px solid #ffd39a;
        }

        .dark .dark-user {
            border: 1px solid #c5ffda;
        }

        .dark .dark-task {
            border: 1px solid #fda5ca;
            margin-left: 15px;
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".collapsible").forEach(row => {
                const child = row.nextElementSibling;
                const chevron = row.querySelector(".chevron");

                if (child) {
                    child.style.display = "block";
                    chevron?.classList.add("open");
                }

                row.addEventListener("click", () => {
                    if (child.style.display === "block") {
                        child.style.display = "none";
                        chevron?.classList.remove("open");
                    } else {
                        child.style.display = "block";
                        chevron?.classList.add("open");
                    }
                });
            });
        });
    </script>

    @php
        function formatDuration($minutes)
        {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;

            if ($hours > 0 && $mins > 0) {
                return "{$hours} hr {$mins} min";
            } elseif ($hours > 0) {
                return "{$hours} hr";
            } else {
                return "{$mins} min";
            }
        }
    @endphp

    @php
        $filterDepartments = $record
            ->filters()
            ->where('param_type', App\Models\Department::class)
            ->pluck('param_id')
            ->toArray();
        $filterClients = $record
            ->filters()
            ->where('param_type', App\Models\Client::class)
            ->pluck('param_id')
            ->toArray();
        $filterProjects = $record
            ->filters()
            ->where('param_type', App\Models\Project::class)
            ->pluck('param_id')
            ->toArray();
        $filterUsers = $record->filters()->where('param_type', App\Models\User::class)->pluck('param_id')->toArray();
    @endphp

    @php
        $allDepartmentTotalMinutes = $record->departments
            ->whereIn('id', $filterDepartments)
            ->flatMap(function ($department) use ($filterClients) {
                $clients = \App\Models\Client::where('department_id', $department->id)
                    ->whereIn('id', $filterClients)
                    ->get();

                return $clients->flatMap->projects->flatMap(function ($project) {
                    return $project->users->map(function ($user) use ($project) {
                        return \App\Models\Task::where('project_id', $project->id)
                            ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                            ->with('timeEntries')
                            ->get()
                            ->flatMap->timeEntries->sum('duration');
                    });
                });
            })
            ->sum();
    @endphp


    <div x-data="{ open: false }" class="flex items-center justify-end w-full gap-1">

        <div class="font-bold text-xl">
            Note: Cost is calculated based on salary.
        </div>

        <div class="relative" @mouseenter="open = true" @mouseleave="open = false">
            <x-heroicon-o-question-mark-circle class="w-5 h-5 cursor-pointer" />

            <div x-show="open" x-transition
                class="absolute right-0 mb-1 mt-2
                   w-max max-w-xs p-2 text-sm bg-gray-800 text-white
                   rounded shadow-lg z-50">
                DaySalary = User Salary / WorkingDayOfMonth (Setting);
                HourSalary = DaySalary / WorkingHourOfDay (Setting);
                MinuteSalary = HourSalary / 60;
                Cost = round(MinuteSalary * Task minutes);
            </div>
        </div>

    </div>

    <div class="w-full p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">

        <div class="flex justify-between font-bold text-xl">
            <div>
                {{ $record->name }}
                <span>
                    {{ $allDepartmentTotalMinutes > 0 ? '(' . formatDuration($allDepartmentTotalMinutes) . ')' : '(0 hr)' }}
                </span>
            </div>
            <div>{{ $record->start_date->format('d M Y') }} - {{ $record->end_date->format('d M Y') }}</div>
        </div>

        @php $hasData = false; @endphp

        @foreach ($record->departments->whereIn('id', $filterDepartments) as $department)
            @php
                $clients = \App\Models\Client::where('department_id', $department->id)
                    ->whereIn('id', $filterClients)
                    ->get();

                $departmentTotalMinutes = $clients->flatMap->projects
                    ->flatMap(function ($project) {
                        return $project->users->map(function ($user) use ($project) {
                            return \App\Models\Task::where('project_id', $project->id)
                                ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                                ->with('timeEntries')
                                ->get()
                                ->flatMap->timeEntries->sum('duration');
                        });
                    })
                    ->sum();

                $departmentHours = floor($departmentTotalMinutes / 60);
                $departmentMinutes = $departmentTotalMinutes % 60;
            @endphp

            @if ($departmentTotalMinutes > 0)
                @php $hasData = true; @endphp
                <div class="tree-row collapsible light-department dark-department font-bold text-lg">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
                        <div class="tree-left font-bold text-lg">
                            <x-heroicon-o-building-office class="w-5 h-5" />
                            {{ $department->name }}
                        </div>
                    </div>

                    @php
                        $clients = \App\Models\Client::where('department_id', $department->id)
                            ->whereIn('id', $filterClients)
                            ->get();

                        $departmentCost = 0;

                        foreach ($clients as $client) {
                            $clientCost = 0;

                            foreach ($client->projects->whereIn('id', $filterProjects) as $project) {
                                $totalMinutes = $project->users
                                    ->map(function ($user) use ($project) {
                                        return \App\Models\Task::where('project_id', $project->id)
                                            ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                                            ->with('timeEntries')
                                            ->get()
                                            ->flatMap->timeEntries->sum('duration');
                                    })
                                    ->sum();

                                if ($totalMinutes > 0) {
                                    if ($project->budget_type == 0) {
                                        $projectCost = ($totalMinutes / 60) * $project->price;
                                    } else {
                                        $projectCost = $project->price;
                                    }

                                    $clientCost += $projectCost;
                                } else {
                                    $projectCost = $projectCost ?? 0;
                                }

                                $project->computed_cost = $projectCost ?? 0;
                            }

                            $client->computed_cost = $clientCost;

                            $departmentCost += $clientCost;
                        }

                        $departmentPercentage =
                            $allDepartmentTotalMinutes > 0
                                ? ($departmentTotalMinutes / $allDepartmentTotalMinutes) * 100
                                : 0;
                    @endphp


                    <div x-data="{ open: false }" class="text-right flex gap-1 items-center">
                        <div>
                            {{ formatDuration($departmentTotalMinutes) }}
                            ({{ number_format($departmentPercentage, 2) }} %)
                            – [Cost: {{ number_format($departmentCost, 2) }}]
                        </div>

                        <div class="relative" @mouseenter="open = true" @mouseleave="open = false">
                            <x-heroicon-o-question-mark-circle class="w-5 h-5 cursor-pointer" />

                            <div x-show="open" x-transition
                                class="absolute right-0 mb-1 bottom-5 w-max max-w-xs p-2 text-sm bg-gray-800 text-white rounded shadow-lg z-50">
                                The cost of all users will be counted and its total will be shown here.
                            </div>
                        </div>
                    </div>

                </div>

                <div class="tree-children">

                    @php
                        $clients = \App\Models\Client::where('department_id', $department->id)
                            ->whereIn('id', $filterClients)
                            ->get();
                    @endphp

                    @foreach ($clients as $client)
                        @php
                            $clientTotalMinutes = $client->projects
                                ->map(function ($project) {
                                    return $project->users
                                        ->map(function ($user) use ($project) {
                                            return \App\Models\Task::where('project_id', $project->id)
                                                ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                                                ->with('timeEntries')
                                                ->get()
                                                ->flatMap->timeEntries->sum('duration');
                                        })
                                        ->sum();
                                })
                                ->sum();

                            $clientHours = floor($clientTotalMinutes / 60);
                            $clientMinutes = $clientTotalMinutes % 60;
                        @endphp
                        @if ($clientTotalMinutes > 0)
                            <div class="tree-row collapsible light-client dark-client font-bold text-lg">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
                                    <div class="tree-left font-bold text-lg">
                                        <x-heroicon-o-user class="w-5 h-5" />
                                        {{ $client->name }}
                                    </div>
                                </div>
                                @php
                                    $clientCost = 0;

                                    foreach ($client->projects->whereIn('id', $filterProjects) as $project) {
                                        $totalMinutes = $project->users
                                            ->map(function ($user) use ($project) {
                                                return \App\Models\Task::where('project_id', $project->id)
                                                    ->whereHas(
                                                        'taskAssignee',
                                                        fn($q) => $q->where('user_id', $user->id),
                                                    )
                                                    ->with('timeEntries')
                                                    ->get()
                                                    ->flatMap->timeEntries->sum('duration');
                                            })
                                            ->sum();

                                        if ($totalMinutes > 0) {
                                            if ($project->budget_type == 0) {
                                                $projectCost = ($totalMinutes / 60) * $project->price;
                                            } else {
                                                $projectCost = $project->price;
                                            }

                                            $clientCost += $projectCost;
                                        }

                                        $project->computed_cost = $projectCost ?? 0;
                                    }

                                    $clientTotalMinutes = $client->projects
                                        ->whereIn('id', $filterProjects)
                                        ->map(function ($project) {
                                            return $project->users
                                                ->map(function ($user) use ($project) {
                                                    return \App\Models\Task::where('project_id', $project->id)
                                                        ->whereHas(
                                                            'taskAssignee',
                                                            fn($q) => $q->where('user_id', $user->id),
                                                        )
                                                        ->with('timeEntries')
                                                        ->get()
                                                        ->flatMap->timeEntries->sum('duration');
                                                })
                                                ->sum();
                                        })
                                        ->sum();

                                    $clientPercentage =
                                        $allDepartmentTotalMinutes > 0
                                            ? ($clientTotalMinutes / $allDepartmentTotalMinutes) * 100
                                            : 0;

                                    $client->computed_cost = $clientCost;
                                @endphp

                                <div class="text-right">
                                    {{ formatDuration($clientTotalMinutes) }}
                                    ({{ number_format($clientPercentage, 2) }} %)
                                    – [Cost: {{ number_format($clientCost, 2) }}]
                                </div>

                            </div>

                            <div class="tree-children">

                                @foreach ($client->projects->whereIn('id', $filterProjects) as $project)
                                    @php
                                        $totalMinutes = $project->users
                                            ->map(function ($user) use ($project) {
                                                return \App\Models\Task::where('project_id', $project->id)
                                                    ->whereHas(
                                                        'taskAssignee',
                                                        fn($q) => $q->where('user_id', $user->id),
                                                    )
                                                    ->with('timeEntries')
                                                    ->get()
                                                    ->flatMap->timeEntries->sum('duration');
                                            })
                                            ->sum();

                                        $hours = floor($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;
                                    @endphp
                                    @if ($totalMinutes > 0)
                                        <div class="tree-row collapsible light-project dark-project">
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
                                                <div class="tree-left font-semibold">
                                                    <x-heroicon-o-folder-open class="w-5 h-5" />
                                                    {{ $project->name }}
                                                </div>
                                            </div>

                                            @php
                                                $projectCost = 0;
                                                if ($project->budget_type == 0) {
                                                    $projectCost = ($totalMinutes / 60) * $project->price;
                                                } else {
                                                    $projectCost = $project->price;
                                                }
                                                $projectPercentage =
                                                    $allDepartmentTotalMinutes > 0
                                                        ? ($totalMinutes / $allDepartmentTotalMinutes) * 100
                                                        : 0;
                                            @endphp

                                            <div class="text-right">
                                                {{ formatDuration($totalMinutes) }}
                                                ({{ number_format($projectPercentage, 2) }} %)
                                                – [Cost: {{ number_format($projectCost, 2) }}]
                                            </div>

                                        </div>

                                        <div class="tree-children">

                                            @foreach ($project->users->whereIn('id', $filterUsers) as $user)
                                                @php
                                                    $tasks = \App\Models\Task::where('project_id', $project->id)
                                                        ->whereHas(
                                                            'taskAssignee',
                                                            fn($q) => $q->where('user_id', $user->id),
                                                        )
                                                        ->with('timeEntries')
                                                        ->get();

                                                    $totalMinutes = $tasks->flatMap->timeEntries->sum('duration');
                                                    $hours = floor($totalMinutes / 60);
                                                    $minutes = $totalMinutes % 60;
                                                @endphp

                                                @if ($totalMinutes > 0)
                                                    <div class="tree-row collapsible light-user dark-user">
                                                        <div class="flex items-center gap-2">
                                                            <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
                                                            <div class="tree-left">
                                                                <x-heroicon-o-user-group class="w-5 h-5" />
                                                                {{ $user->name }}
                                                            </div>
                                                        </div>

                                                        @php
                                                            $userCost = 0;
                                                            foreach ($tasks as $task) {
                                                                $taskMinutes = $task->timeEntries->sum('duration');
                                                                if ($project->budget_type == 0) {
                                                                    $userCost += ($taskMinutes / 60) * $project->price;
                                                                } else {
                                                                    $projectMinutes = $project->users
                                                                        ->map(function ($u) use ($project) {
                                                                            return \App\Models\Task::where(
                                                                                'project_id',
                                                                                $project->id,
                                                                            )
                                                                                ->whereHas(
                                                                                    'taskAssignee',
                                                                                    fn($q) => $q->where(
                                                                                        'user_id',
                                                                                        $u->id,
                                                                                    ),
                                                                                )
                                                                                ->with('timeEntries')
                                                                                ->get()
                                                                                ->flatMap->timeEntries->sum('duration');
                                                                        })
                                                                        ->sum();
                                                                    $userCost +=
                                                                        $projectMinutes > 0
                                                                            ? ($taskMinutes / $projectMinutes) *
                                                                                $project->price
                                                                            : 0;
                                                                }
                                                            }
                                                            $userPercentage =
                                                                $allDepartmentTotalMinutes > 0
                                                                    ? ($totalMinutes / $allDepartmentTotalMinutes) * 100
                                                                    : 0;
                                                        @endphp

                                                        <div class="text-right">
                                                            {{ formatDuration($totalMinutes) }}
                                                            ({{ number_format($userPercentage, 2) }} %)
                                                            – [Cost: {{ number_format($userCost, 2) }}]
                                                        </div>

                                                    </div>

                                                    <div class="tree-children">

                                                        @php
                                                            $tasks = \App\Models\Task::where('project_id', $project->id)
                                                                ->whereHas(
                                                                    'taskAssignee',
                                                                    fn($q) => $q->where('user_id', $user->id),
                                                                )
                                                                ->get();
                                                        @endphp

                                                        @foreach ($tasks as $task)
                                                            @php
                                                                $totalMinutes = $task->timeEntries->sum('duration');
                                                                $hours = floor($totalMinutes / 60);
                                                                $minutes = $totalMinutes % 60;
                                                            @endphp
                                                            @if ($totalMinutes > 0)
                                                                <div class="tree-row light-task dark-task">
                                                                    <div class="tree-left">
                                                                        <x-heroicon-o-clipboard-document-list
                                                                            class="w-5 h-5" />
                                                                        {{ $task->title }}
                                                                    </div>

                                                                    <div>
                                                                        {{ formatDuration($totalMinutes) }}
                                                                    </div>

                                                                </div>
                                                            @endif
                                                        @endforeach

                                                    </div>
                                                @endif
                                            @endforeach

                                        </div>
                                    @endif
                                @endforeach

                            </div>
                        @endif
                    @endforeach

                </div>
            @endif
        @endforeach

        @if (!$hasData)
            <div class="text-center text-gray-500 font-semibold text-lg">
                No record available.
            </div>
        @endif

    </div>

</x-filament-panels::page>
