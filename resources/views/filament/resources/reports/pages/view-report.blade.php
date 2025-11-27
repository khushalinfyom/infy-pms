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
        }

        .light-client {
            border: 1px solid #00aaff;
        }

        .light-project {
            border: 1px solid rgb(255, 174, 68);
        }

        .light-user {
            border: 1px solid rgb(46, 255, 119);
        }

        .light-task {
            border: 1px solid rgb(255, 238, 48);
        }

        /* .dark .dark-department {
            background: #1f2937;
            color: #e5e7eb;
        }

        .dark .dark-client {
            background: #0c4a6e;
            color: #e0f2fe;
        }

        .dark .dark-project {
            background: #7c2d12;
            color: #ffedd5;
        }

        .dark .dark-user {
            background: #14532d;
            color: #dcfce7;
        }

        .dark .dark-task {
            background: #854d0e;
            color: #fef9c3;
        } */
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".collapsible").forEach(row => {
                row.addEventListener("click", () => {
                    const child = row.nextElementSibling;
                    const chevron = row.querySelector(".chevron");

                    if (child.style.display === "none" || child.style.display === "") {
                        child.style.display = "block";
                        chevron.classList.add("open");
                    } else {
                        child.style.display = "none";
                        chevron.classList.remove("open");
                    }
                });
            });
        });
    </script>

    <div class="flex justify-between mb-4">
        <div>{{ $record->name }}</div>
        <div>{{ $record->start_date->format('d M Y') }} - {{ $record->end_date->format('d M Y') }}</div>
    </div>

    @foreach ($record->departments as $department)
        <div class="tree-row collapsible light-department dark-department">
            <div class="tree-left">
                <x-heroicon-o-building-office class="w-5 h-5" />
                {{ $department->name }}
            </div>
            <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
        </div>

        <div class="tree-children">

            @php
                $clients = \App\Models\Client::where('department_id', $department->id)->get();
            @endphp


            @foreach ($clients as $client)
                <div class="tree-row collapsible light-client dark-client">
                    <div class="tree-left">
                        <x-heroicon-o-user class="w-5 h-5" />
                        {{ $client->name }}
                    </div>
                    <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
                </div>

                <div class="tree-children">

                    @foreach ($client->projects as $project)
                        <div class="tree-row collapsible light-project dark-project">
                            <div class="tree-left">
                                <x-heroicon-o-folder-open class="w-5 h-5" />
                                {{ $project->name }}
                            </div>
                            <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
                        </div>

                        <div class="tree-children">

                            @foreach ($project->users as $user)
                                <div class="tree-row collapsible light-user dark-user">
                                    <div class="tree-left">
                                        <x-heroicon-o-user-group class="w-5 h-5" />
                                        {{ $user->name }}
                                    </div>
                                    <x-heroicon-o-chevron-right class="w-4 h-4 chevron" />
                                </div>

                                <div class="tree-children">

                                    @php
                                        $tasks = \App\Models\Task::where('project_id', $project->id)
                                            ->whereHas('taskAssignee', fn($q) => $q->where('user_id', $user->id))
                                            ->get();
                                    @endphp

                                    @foreach ($tasks as $task)
                                        <div class="tree-row light-task dark-task">
                                            <div class="tree-left">
                                                <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                                                {{ $task->title }}
                                            </div>
                                        </div>

                                        <div class="tree-children">
                                        </div>
                                    @endforeach

                                </div>
                            @endforeach

                        </div>
                    @endforeach

                </div>
            @endforeach

        </div>
    @endforeach

</x-filament-panels::page>
