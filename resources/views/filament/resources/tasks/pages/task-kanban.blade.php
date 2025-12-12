<x-filament-panels::page>
    <div>
        <div>
            <!-- Top bar -->
            <div class="flex items-center gap-4 mb-4 flex-wrap justify-between">
                <!-- Left: search -->
                <div class="w-100">
                    <x-filament::input.wrapper>
                        <div class="flex items-center gap-2 flex-1 min-w-60 max-w-xl">
                            <div
                                class="flex items-center bg-white dark:bg-gray-800 rounded-md shadow px-3 gap-2 w-full border border-gray-200 dark:border-gray-700">

                                @svg('phosphor-magnifying-glass', ['class' => 'sm:w-5 sm:h-5 w-4 h-4 mr-2 text-gray-600 dark:text-gray-300'])
                                <x-filament::input id="searchInput" placeholder="Search tasks by title..." type="search"
                                    class="w-full outline-none text-sm text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 bg-transparent" />
                            </div>
                        </div>
                    </x-filament::input.wrapper>
                </div>

                <!-- Right: filters and create button -->
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="md:min-w-[500px] min-w-[300px]">
                        {{ $this->filterForm }}
                    </div>
                    {{ $this->taskListAction }}
                    {{ $this->createTaskAction }}
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="bg-transparent" wire:ignore>
                <div id="board-container" class="overflow-x-auto pb-4">
                    <div id="board" class="flex gap-4 min-w-max">
                        <!-- Columns will be rendered here by JS -->
                    </div>
                </div>
            </div>

            <div class="mt-6 text-sm text-slate-500">Tip: click the + on column headers to quickly add to that column.
                Click
                a task to edit due date / add user.</div>
        </div>


    </div>

    @push('styles')
        <style>
            /* small custom styles for scrollbars & avatar stacking */
            .scrollbar-thin::-webkit-scrollbar {
                height: 8px;
                width: 8px
            }

            .scrollbar-thin::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.12);
                border-radius: 999px
            }

            .avatar-stack {
                display: flex;
                align-items: center;
            }

            .avatar {
                width: 32px;
                height: 32px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 13px;
                color: white;
                border: 2px solid #fff;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
            }

            .card-dragging {
                opacity: 0.85;
                transform: rotate(1deg);
            }

            .col-scroll {
                max-height: 65vh;
                overflow: auto;
            }

            #board-container {
                min-height: 500px;
            }

            .task {
                transition: all 0.2s ease;
            }

            .task:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            /* Loading indicator */
            .kanban-loading {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 330px;
                min-width: 330px;
                font-size: 1.2rem;
                color: #6b7280;
            }

            /* Column styling */
            .kanban-column {
                min-width: 330px;
                width: 330px;
                background-color: #f8f9fe;
            }

            /* Remove horizontal scroll from columns */
            .col-scroll {
                overflow-x: hidden;
                overflow-y: auto;
            }

            .task-number {
                position: relative;
            }

            .task-number::before {
                content: "";
                display: inline-block;
                position: absolute;
                top: 0px;
                left: -9px;
                width: 4px;
                height: 100%;
                border-radius: 0 13px 10px 0;
                background-color: #6b7280;
            }

            /* GLOBAL SCROLLBAR STYLE (applies to whole page) */
            ::-webkit-scrollbar {
                width: 10px;
                height: 10px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f5f9;
                /* slate-100 */
                border-radius: 10px;
            }

            ::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                /* slate-300 */
                border-radius: 10px;
                border: 2px solid #f1f5f9;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
                /* slate-400 */
            }

            /* MAKE SCROLLBAR OVERLAY ON HOVER (optional) */
            .scrollbar-hover:hover::-webkit-scrollbar-thumb {
                background: #64748b;
            }

            /* SHORTER ROUNDED THIN SCROLLBAR FOR COLUMNS */
            .col-scroll::-webkit-scrollbar {
                width: 6px;
            }

            .col-scroll::-webkit-scrollbar-thumb {
                background: #d1d5db;
                /* gray-300 */
                border-radius: 999px;
            }

            .col-scroll::-webkit-scrollbar-thumb:hover {
                background: #9ca3af;
                /* gray-400 */
            }

            /* ================================
                          DARK MODE SUPPORT
                       ================================ */
            .dark {
                color-scheme: dark;
            }

            /* Board container background */
            .dark #board-container {
                background-color: #020617;
                /* slate-950 - even darker background */
            }

            /* Column background */
            .dark .kanban-column {
                background-color: #0f172a;
                /* slate-900 - darker column background */
                border-color: #1e293b;
                /* slate-800 - darker borders */
            }

            /* Column header text */
            .dark .kanban-column .text-sm,
            .dark .kanban-column .text-xs {
                color: #dce7f2 !important;
                /* White text */
            }

            /* Task card */
            .dark .task {
                background-color: #172033;
                /* slate-900 - darker card background */
                border-color: #314157;
                /* slate-800 - darker borders */
            }

            .dark .task:hover {
                box-shadow: 0 4px 6px -1px rgba(2, 6, 23, 0.4);
                /* slate-950 with opacity */
            }

            /* Task number left bar */
            .dark .task-number::before {
                background-color: #334155;
                /* slate-700 - darker accent */
            }

            /* Text inside tasks */
            .dark .task .text-sm,
            .dark .task .text-xs {
                color: #cbd5e1;
                /* slate-300 - dimmer task text */
            }

            /* Project chip */
            .dark .task .bg-slate-100 {
                background-color: #1e293b !important;
                /* slate-800 - darker chip background */
                color: #cbd5e1 !important;
                /* slate-300 - dimmer chip text */
            }

            /* Avatar placeholder */
            .dark .avatar {
                border-color: #0f172a !important;
                /* slate-900 - darker avatar border */
            }

            .dark .avatar.bg-slate-300 {
                background-color: #334155 !important;
                /* slate-700 - darker NA avatar */
                color: #94a3b8 !important;
                /* slate-400 - dimmer NA text */
            }

            /* Add task button */
            .dark .kanban-column button.bg-slate-50 {
                background-color: #1e293b !important;
                /* slate-800 - darker button bg */
                color: #cbd5e1 !important;
                /* slate-300 - dimmer button text */
                border-color: #334155 !important;
                /* slate-700 - darker button border */
            }

            .dark .kanban-column button.bg-slate-50:hover {
                background-color: #334155 !important;
                /* slate-700 - hover bg */
            }

            /* Column header small add button */
            .dark .kanban-column button.bg-slate-100 {
                background-color: #1e293b !important;
                /* slate-800 - darker button bg */
                color: #cbd5e1 !important;
                /* slate-300 - dimmer button text */
            }

            .dark .kanban-column button.bg-slate-100:hover {
                background-color: #334155 !important;
                /* slate-700 - hover bg */
            }

            /* Scrollbars */
            .dark ::-webkit-scrollbar-track {
                background: #0f172a;
                /* slate-900 - darker track */
            }

            .dark ::-webkit-scrollbar-thumb {
                background: #334155;
                /* slate-700 - darker thumb */
                border: 2px solid #0f172a;
                /* slate-900 - darker thumb border */
            }

            .dark ::-webkit-scrollbar-thumb:hover {
                background: #475569;
                /* slate-600 - hover thumb */
            }

            /* Column thin scrollbars */
            .dark .col-scroll::-webkit-scrollbar-thumb {
                background: #334155;
                /* slate-700 - darker thumb */
            }

            .dark .col-scroll::-webkit-scrollbar-thumb:hover {
                background: #475569;
                /* slate-600 - hover thumb */
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

        <script>
            document.addEventListener('livewire:navigated', () => {
                window.kanbanWire = Livewire.find('{{ $this->getId() }}');
                if (!window.kanbanWire) {
                    console.warn("KanbanWire not found yet...");
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                initializeBoard();
            });

            const tasksData = @json($this->tasks);
            const statusesData = @json($this->statuses);
            let usersData = @json($this->users);
            let kanbanState = {
                columns: [],
                tasks: [],
            };

            function getColumnDesign(col) {
                const tasksInCol = kanbanState.tasks.filter(t => t.column == col.id);

                const colEl = document.createElement('div');
                colEl.className = 'rounded-md shadow p-3 flex flex-col kanban-column';
                colEl.dataset.col = col.id;

                colEl.innerHTML = `
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-semibold">${col.title}</div>
                                        <div id="count-${col.id}" class="text-xs text-slate-500">(${tasksInCol.length})</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600" onclick="openCreateTaskModal('${col.id}')">+</button>

                                </div>
                            </div>

                            <div class="col-scroll mb-2">
                                <div id="list-${col.id}" class="space-y-2 px-1 min-h-[50px]"></div>
                            </div>

                            <div class="pt-2">
                                <button class="w-full text-sm border rounded-md px-3 py-2 bg-slate-50 hover:bg-slate-100" onclick="openCreateTaskModal('${col.id}')">+ Add Task</button>
                            </div>
                            `;

                const listEl = colEl.querySelector(`#list-${col.id}`);

                if (tasksInCol.length === 0) {
                    // Show empty state when no tasks
                    const emptyState = document.createElement('div');
                    emptyState.className =
                        'flex flex-col items-center justify-center py-4 text-slate-500 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-200 dark:border-slate-700 empty-state';
                    emptyState.innerHTML = `
                        <div class="relative mb-2">
                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold mb-1 dark:text-white">There's no Task</p>
                        <p class="text-xs w-70 text-center leading-tight text-slate-500 dark:text-slate-400">Click + to add your first task.</p>
                    `;
                    listEl.appendChild(emptyState);
                } else {
                    // Show tasks when available
                    tasksInCol.forEach(task => {
                        const card = document.createElement('div');
                        card.className =
                            'task bg-white rounded-md pt-3 pb-2 pr-3 pl-2 border border-slate-200 cursor-grab';
                        card.dataset.id = task.id;

                        card.innerHTML = getCardDesign(task);

                        card.querySelector('[data-add-user]')?.addEventListener('click', (e) => {
                            e.stopPropagation();
                            openAssignPopover(task.id, e.currentTarget);
                        });

                        card.querySelector('[data-calendar]')?.addEventListener('click', (e) => {
                            e.stopPropagation();
                            openDuePopover(task.id, e.currentTarget);
                        });

                        card.addEventListener('click', () => openTaskEditModal(task.id));

                        listEl.appendChild(card);
                    });
                }

                return colEl;
            }

            function getCardDesign(task) {
                // More robust way to get assigned users
                const assigned = (task.fullAssignees && Array.isArray(task.fullAssignees)) ? task.fullAssignees : [];

                let avatarHtml = '';
                if (assigned.length === 0) {
                    avatarHtml = `
                                <div data-add-user="${task.id}"
                                    class="avatar bg-slate-300 dark:bg-slate-600 text-white dark:text-slate-300 cursor-pointer flex items-center justify-center
                                            w-7 h-7 rounded-full border-2 border-white dark:border-slate-800 shadow">
                                    <span class="text-xs">NA</span>
                                </div>`;
                } else {
                    const user = assigned[0];
                    // More robust way to get user image
                    const img = (user && (user.img_avatar || user.image_path)) || null;
                    avatarHtml = `
                <div data-add-user="${task.id}" class="cursor-pointer relative">
                    ${img ? `<img src="${img}" title="${user.name}" class="w-7 h-7 rounded-full object-cover shadow" />` : `<div class="avatar bg-slate-500 dark:bg-slate-600 text-white dark:text-slate-300 flex items-center justify-center w-7 h-7 rounded-full text-xs">${user && user.name ? user.name.split(" ").map(p => p[0]).join("").toUpperCase() : 'U'}</div>`}
                    ${assigned.length > 1 ? `<div class="absolute -bottom-1 -right-1 bg-blue-600 text-white text-[8px] px-1 py-0.5 rounded-full flex items-center justify-center shadow">+${assigned.length - 1}</div>` : ''}
                </div>`;
                }

                return `
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-start gap-2 justify-between w-full">
                                    <div class="flex items-start gap-2">
                                        <div class="task-number text-xs text-slate-500 rounded-sm bg-slate-100 px-2 py-1">
                                            ${task.number || ''}
                                        </div>
                                        <div class="text-sm font-medium cursor-pointer hover:underline" onclick="viewTaskDetails('${task.id}')">${escapeHtml(task.title)}</div>
                                    </div>
                                    <div class="relative">
                                        <button class="text-slate-400 hover:text-slate-600" onclick="toggleTaskDropdown(event, '${task.id}')">
                                            <!-- Vertical 3 dots icon -->
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-width="2" d="M12 5.25a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                            </svg>
                                        </button>
                                        <!-- Dropdown menu -->
                                        <div id="task-dropdown-${task.id}"
                                            class="absolute right-0 mt-2 w-48
                                                    bg-white dark:bg-slate-800
                                                    rounded-md shadow-lg py-1 hidden z-50
                                                    border border-slate-200 dark:border-slate-700">

                                            <button onclick="editTask('${task.id}')"
                                                class="block w-full text-left px-4 py-2 text-sm
                                                    text-slate-700 dark:text-slate-200
                                                    hover:bg-slate-100 dark:hover:bg-slate-700">
                                                Edit Task
                                            </button>

                                            <button onclick="deleteTask('${task.id}')"
                                                class="block w-full text-left px-4 py-2 text-sm
                                                    text-slate-700 dark:text-slate-200
                                                    hover:bg-slate-100 dark:hover:bg-slate-700">
                                                Delete Task
                                            </button>

                                            <button onclick="addTimeEntry('${task.id}')"
                                                class="block w-full text-left px-4 py-2 text-sm
                                                    text-slate-700 dark:text-slate-200
                                                    hover:bg-slate-100 dark:hover:bg-slate-700">
                                                New Time Entry
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                ${task.tags && task.tags.length > 0 ? `<div class="flex flex-wrap gap-1 mt-2"><span class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-xs cursor-pointer" onclick="viewTaskTags('${task.id}')" title="Click to view all tags"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>${task.tags.length} Tag${task.tags.length !== 1 ? 's' : ''}</span></div>` : '<div class="flex flex-wrap gap-1 mt-2"><span class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-xs"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>No Tags</span></div>'}
                            </div>
                        </div>

                        <hr class="mt-3 mb-2 border-slate-200" />

                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="avatar-stack relative">${avatarHtml}</div>
                                <div class="text-xs">
                                    <div class="flex items-center gap-2 border border-slate-200 rounded-full p-1 cursor-pointer calendar-btn"
                                        data-calendar="${task.id}">
                                            @svg('phosphor-calendar-dots', ['class' => 'w-4 h-4'])
                                    </div>
                                </div>
                            </div>
                        </div>
                        `;
            }

            //#f8f9fe

            // Helper function for escaping HTML
            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            // Helper function for formatting due dates
            function formatDue(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr + 'T00:00:00');
                return d.toLocaleDateString();
            }

            function avatarFor(userId) {
                // Find full user object from tasksData (where full user information exists)
                let userObj = null;
                for (let t of kanbanState.tasks) {
                    if (t.assigned && t.assigned.length && t.assigned.includes(userId)) {
                        const index = t.assigned.indexOf(userId);
                        if (t.fullAssignees && t.fullAssignees[index]) {
                            userObj = t.fullAssignees[index];
                        }
                    }
                }

                // Fallback: find in tasksData original
                if (!userObj) {
                    const fromRaw = tasksData.find(t => (t.task_assignee || []).find(a => a.id === userId));
                    if (fromRaw) {
                        userObj = fromRaw.task_assignee.find(a => a.id === userId);
                    }
                }

                const name = userObj?.name || "User";
                const img = userObj?.img_avatar || userObj?.image_path || null;

                if (img) {
                    return `
                            <img src="${img}"
                                title="${name}"
                                class="avatar rounded-full w-8 h-8 object-cover border-2 border-white shadow" />
                        `;
                }

                // fallback initials
                const initials = name.split(" ").map(p => p[0]).join("").toUpperCase();
                return `
                        <div class="avatar bg-slate-500 text-white" title="${name}">
                            ${initials}
                        </div>
                    `;
            }

            // Helper function for initializing sortables
            function initSortables() {
                // destroy previous
                if (window.sortInstances) {
                    window.sortInstances.forEach(s => s.destroy && s.destroy());
                }
                window.sortInstances = [];

                kanbanState.columns.forEach(col => {
                    const list = document.querySelector(`#list-${col.id}`);
                    if (!list) return;
                    const s = new Sortable(list, {
                        group: 'kanban-group',
                        animation: 180,
                        ghostClass: 'card-dragging',
                        filter: '.add-task-btn, .empty-state', 
                        onEnd: function(evt) {
                            const el = evt.item;
                            const id = el.dataset.id;
                            const newCol = evt.to.closest('[data-col]').dataset.col;
                            const newIndex = Array.from(evt.to.children).indexOf(el);
                            const t = kanbanState.tasks.find(x => x.id == id);
                            if (!t) return;
                            t.column = newCol;
                            const colTasks = kanbanState.tasks.filter(x => x.column == newCol && x.id !=
                                id);
                            colTasks.splice(newIndex, 0, t);
                            const others = kanbanState.tasks.filter(x => x.column != newCol);
                            kanbanState.tasks = others.concat(colTasks);
                            window.kanbanWire.call('taskMoved', id, newCol, newIndex);
                        }
                    });
                    window.sortInstances.push(s);
                });
            }

            // Helper function for updating counts
            function updateAllCounts() {
                kanbanState.columns.forEach(c => {
                    const countEl = document.getElementById('count-' + c.id);
                    if (countEl) {
                        countEl.textContent = `(${kanbanState.tasks.filter(t => t.column == c.id).length})`;
                    }
                });
            }

            // Client-side filtering function
            function applyClientFilters() {
                const searchInput = document.getElementById('searchInput');
                const q = searchInput ? (searchInput.value || '').toLowerCase() : '';

                // Apply client-side search filter only (other filters are handled by backend)
                kanbanState.tasks.forEach(t => {
                    const el = document.querySelector(`[data-id="${t.id}"]`);
                    // If DOM not yet rendered, skip - renderBoard will enforce filters on next render
                    if (!el) return;

                    let visible = true;
                    if (q && !t.title.toLowerCase().includes(q)) visible = false;
                    el.style.display = visible ? '' : 'none';
                });
            }

            // Add event listener for search input
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.addEventListener('input', applyClientFilters);
                }
            });

            // Global render function
            function renderBoard() {
                const boardEl = document.getElementById('board');
                if (!boardEl) return;
                boardEl.innerHTML = '';
                kanbanState.columns.forEach(col => {
                    const colEl = getColumnDesign(col);
                    boardEl.appendChild(colEl);
                });
                // re-init sortables after render
                initSortables();
                updateAllCounts();
                applyClientFilters();
            }

            function initializeBoard() {
                /* ---------- State ---------- */
                kanbanState = {
                    columns: statusesData.map(status => ({
                        id: status.status,
                        title: status.name
                    })),
                    tasks: tasksData.map(task => {
                        // More robust mapping for initialization
                        const mappedTask = {
                            id: task.id,
                            number: task.task_number ? task.task_number : '',
                            title: task.title,
                            column: task.status,
                            project: task.project_id,
                            projectName: task.project ? task.project.name : '',
                            tags: task.tags || [],
                            assigned: (task.task_assignee && Array.isArray(task.task_assignee)) ? task
                                .task_assignee.map(a => a.id) : [],
                            assignedNames: (task.task_assignee && Array.isArray(task.task_assignee)) ? task
                                .task_assignee.map(a => a.name) : [],
                            fullAssignees: (task.task_assignee && Array.isArray(task.task_assignee)) ? task
                                .task_assignee : [],
                            due: task.due_date || null,
                            priority: task.priority || null
                        };

                        return mappedTask;
                    }),
                };

                /* ---------- Utilities ---------- */
                function uid(prefix = 't') {
                    return prefix + '-' + Math.random().toString(36).slice(2, 9);
                }

                function getUserInitials(name) {
                    if (!name) return 'U';
                    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
                }

                function openAssignPopover(taskId, anchorEl) {
                    // Call Livewire method to open the Filament modal for assigning users
                    window.kanbanWire.call('assignTaskUsers', taskId);
                }

                function openDuePopover(taskId, anchorEl) {
                    // Call Livewire method to open the Filament modal for setting due date
                    window.kanbanWire.call('setTaskDueDate', taskId);
                }
                window.openAssignPopover = openAssignPopover;
                window.openDuePopover = openDuePopover;

                /* ---------- initial rendering ---------- */
                renderBoard();
            }

            // Refresh data function
            function refreshKanban(data) {
                try {
                    // Check if data is valid and has tasks
                    if (data && Array.isArray(data.tasks)) {
                        const newTasksData = data.tasks;
                        usersData = data.users || {};

                        kanbanState.tasks = newTasksData.map(task => {
                            // More robust mapping
                            const mappedTask = {
                                id: task.id,
                                number: task.task_number ? task.task_number : '',
                                title: task.title,
                                column: task.status,
                                project: task.project_id,
                                projectName: task.project ? task.project.name : '',
                                tags: task.tags || [],
                                assigned: (task.task_assignee && Array.isArray(task.task_assignee)) ? task
                                    .task_assignee.map(a => a.id) : [],
                                assignedNames: (task.task_assignee && Array.isArray(task.task_assignee)) ? task
                                    .task_assignee.map(a => a.name) : [],
                                fullAssignees: (task.task_assignee && Array.isArray(task.task_assignee)) ? task
                                    .task_assignee : [],
                                due: task.due_date || null,
                                priority: task.priority || null
                            };

                            return mappedTask;
                        });

                        // Re-render board
                        renderBoard();
                    } else {
                        console.warn('Invalid data received for refreshKanban:', data);
                        // Still re-render to clear the board if needed
                        renderBoard();
                    }
                } catch (error) {
                    console.error('Error refreshing board data:', error);
                }
            }

            // Function to open the create task modal for a specific status
            function openCreateTaskModal(statusId) {
                // Call the Livewire method to create a task in the specific status
                window.kanbanWire.call('addTaskInStatus', statusId);
            }

            // Function to toggle task dropdown menu
            function toggleTaskDropdown(event, taskId) {
                event.stopPropagation();

                // Hide all other dropdowns
                document.querySelectorAll('[id^="task-dropdown-"]').forEach(dropdown => {
                    if (dropdown.id !== `task-dropdown-${taskId}`) {
                        dropdown.classList.add('hidden');
                    }
                });

                // Toggle the clicked dropdown
                const dropdown = document.getElementById(`task-dropdown-${taskId}`);
                dropdown.classList.toggle('hidden');
            }

            // Function to edit a task
            function editTask(taskId) {
                // Hide the dropdown
                document.querySelectorAll('[id^="task-dropdown-"]').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });

                // Call Livewire method to edit the task
                window.kanbanWire.call('editTask', taskId);
            }

            // Function to delete a task
            function deleteTask(taskId) {
                // Hide the dropdown
                document.querySelectorAll('[id^="task-dropdown-"]').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });

                // Call Livewire method to delete the task
                window.kanbanWire.call('deleteTask', taskId);
            }

            // Close dropdowns when clicking elsewhere
            document.addEventListener('click', function(event) {
                // Check if the click was outside any dropdown
                const isDropdownButton = event.target.closest('button[onclick*="toggleTaskDropdown"]');
                const isDropdownMenu = event.target.closest('[id^="task-dropdown-"]');

                if (!isDropdownButton && !isDropdownMenu) {
                    // Hide all dropdowns
                    document.querySelectorAll('[id^="task-dropdown-"]').forEach(dropdown => {
                        dropdown.classList.add('hidden');
                    });
                }
            });

            // Function to add time entry
            function addTimeEntry(taskId) {
                // Hide the dropdown
                document.querySelectorAll('[id^="task-dropdown-"]').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });

                // Call Livewire method to add time entry
                window.kanbanWire.call('addTimeEntry', taskId);
            }

            // Function to show all tags for a task
            function showTaskTags(taskId, event) {
                event.stopPropagation();

                // Find the task
                const task = kanbanState.tasks.find(t => t.id == taskId);
                if (!task || !task.tags || task.tags.length === 0) return;

                // Create tooltip element if it doesn't exist
                let tooltip = document.getElementById('task-tags-tooltip');
                if (!tooltip) {
                    tooltip = document.createElement('div');
                    tooltip.id = 'task-tags-tooltip';
                    tooltip.className =
                        'fixed z-50 bg-white dark:bg-gray-800 rounded-md shadow-lg p-3 border border-gray-200 dark:border-gray-700';
                    tooltip.style.maxWidth = '300px';
                    document.body.appendChild(tooltip);
                }

                // Generate tags HTML
                const tagsHtml = task.tags.map(tag =>
                    `<span class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-xs m-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        ${escapeHtml(tag.name)}
                    </span>`
                ).join('');

                tooltip.innerHTML = `
                    <div class="font-semibold text-sm mb-2">Task Tags</div>
                    <div class="flex flex-wrap">${tagsHtml}</div>
                `;

                // Position tooltip near the cursor
                const x = event.pageX + 10;
                const y = event.pageY + 10;
                tooltip.style.left = x + 'px';
                tooltip.style.top = y + 'px';

                // Show tooltip
                tooltip.style.display = 'block';

                // Hide tooltip when mouse leaves
                tooltip.onmouseleave = () => {
                    tooltip.style.display = 'none';
                };

                // Hide tooltip on click anywhere else
                setTimeout(() => {
                    document.addEventListener('click', function hideTooltip() {
                        tooltip.style.display = 'none';
                        document.removeEventListener('click', hideTooltip);
                    }, {
                        once: true
                    });
                });
            }

            // Function to view task details in a modal
            function viewTaskDetails(taskId) {
                // Call Livewire method to open the task details modal
                window.kanbanWire.call('viewTaskDetails', taskId);
            }

            // Function to view task tags in a modal
            function viewTaskTags(taskId) {
                // Call Livewire method to open the task tags modal
                window.kanbanWire.call('viewTaskTags', taskId);
            }
        </script>
    @endpush
</x-filament-panels::page>
