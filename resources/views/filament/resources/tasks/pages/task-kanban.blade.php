<x-filament-panels::page>
    <div>
        <div>
            <!-- Top bar -->
            <div class="flex items-center gap-4 mb-4 flex-wrap justify-between">
                <!-- Left: search -->
                <div class="flex items-center gap-2 flex-1 min-w-60 max-w-xl">
                    <div class="flex items-center bg-white rounded-md shadow px-3 py-2 gap-2 w-full">
                        @svg('phosphor-magnifying-glass', ['class' => 'sm:w-5 sm:h-5 w-4 h-4  mr-2'])
                        <input id="searchInput" placeholder="Search tasks by title..."
                            class="w-full outline-none text-sm" />
                    </div>
                </div>

                <!-- Right: filters and create button -->
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="md:min-w-[500px] min-w-[300px]">
                        {{ $this->filterForm }}
                    </div>
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

        <!-- Modals -->
        <div id="modalOverlay" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-5">
                <div class="flex justify-between items-center mb-3">
                    <div id="modalTitle" class="font-semibold">Create Task</div>
                    <button id="closeModal" class="text-slate-500 hover:text-slate-700">&times;</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600">Title</label>
                        <input id="modalTitleInput" class="w-full border rounded-md px-3 py-2 mt-1"
                            placeholder="Task title" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-slate-600">Project</label>
                            <select id="modalProject" class="w-full border rounded-md px-2 py-2 mt-1">
                                <option value="">—</option>
                                @foreach ($this->projects as $id => $name)
                                    <option value="{{ $id }}"
                                        {{ $id == $this->project_id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600">Assign to</label>
                            <select id="modalAssignee" class="w-full border rounded-md px-2 py-2 mt-1">
                                <option value="">—</option>
                                @foreach ($this->users as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-slate-600">Column</label>
                            <select id="modalColumn" class="w-full border rounded-md px-2 py-2 mt-1">
                                @foreach ($this->statuses as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600">Due Date (optional)</label>
                            <input id="modalDue" type="date" class="w-full border rounded-md px-2 py-2 mt-1" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button id="modalCancel" class="px-4 py-2 rounded-md text-sm bg-slate-100">Cancel</button>
                        <button id="modalSave" class="px-4 py-2 rounded-md text-sm bg-blue-600 text-white">Save
                            Task</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- small inline popover for adding user/due -->
        <div id="inlinePopover" class="fixed z-50 hidden">
            <div class="bg-white rounded-md shadow p-3 min-w-[220px]">
                <div id="popoverContent"></div>
                <div class="mt-3 flex justify-end gap-2">
                    <button id="popoverCancel" class="text-sm px-3 py-1 rounded-md bg-slate-100">Close</button>
                </div>
            </div>
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
            <button class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600">+</button>
            <button class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-width="1.5" d="M6 12h12M6 6h12M6 18h12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="col-scroll mb-2">
        <div id="list-${col.id}" class="space-y-2 px-1 min-h-[50px]"></div>
    </div>

    <div class="pt-2">
        <button class="w-full text-sm border rounded-md px-3 py-2 bg-slate-50 hover:bg-slate-100">+ Add Task</button>
    </div>
    `;

                const listEl = colEl.querySelector(`#list-${col.id}`);
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

                return colEl;
            }

            function getCardDesign(task) {
                // Avatar calculation inside this function
                let avatarHtml = '';

                const assigned = task.fullAssignees || [];
                if (assigned.length === 0) {
                    avatarHtml = `
        <div data-add-user="${task.id}"
             class="avatar bg-slate-300 text-white cursor-pointer flex items-center justify-center
                    w-10 h-10 rounded-full border-2 border-white shadow">
             <span class="text-xs">NA</span>
        </div>`;
                } else {
                    const user = assigned[0];
                    const img = user.img_avatar || user.image_path || null;
                    avatarHtml = `
        <div data-add-user="${task.id}" class="cursor-pointer relative">
            ${img ? `<img src="${img}" title="${user.name}" class="w-7 h-7 rounded-full object-cover shadow" />` : `<div class="avatar bg-slate-500 text-white">${user.name.split(" ").map(p => p[0]).join("").toUpperCase()}</div>`}
            ${assigned.length > 1 ? `<div class="absolute -bottom-1 -right-1 bg-blue-600 text-white text-[8px] px-1 py-0.5 rounded-full flex items-center justify-center shadow">+${assigned.length - 1}</div>` : ''}
        </div>`;
                }

                return `
    <div class="flex items-start justify-between gap-2">
        <div class="flex-1">
            <div class="flex items-start gap-2">
                <div class="task-number text-xs text-slate-500 rounded-sm bg-slate-100 px-2 py-1">
                    ${task.number || ''}
                </div>
                <div class="text-sm font-medium">${escapeHtml(task.title)}</div>
            </div>

            <div class="mt-2 text-xs text-slate-500">
                ${task.projectName 
                    ? `<span class="inline-block px-2 py-1 bg-slate-100 rounded">${escapeHtml(task.projectName)}</span>` 
                    : ''}
            </div>
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
                    tasks: tasksData.map(task => ({
                        id: task.id,
                        number: task.task_number ? task.task_number : '',
                        title: task.title,
                        column: task.status,
                        project: task.project_id,
                        projectName: task.project ? task.project.name : '',
                        tags: task.tags || [],
                        assigned: task.task_assignee ? task.task_assignee.map(a => a.id) : [],
                        assignedNames: task.task_assignee ? task.task_assignee.map(a => a.name) : [],
                        fullAssignees: task.task_assignee || [],
                        due: task.due_date || null,
                        priority: task.priority || null
                    })),
                };

                /* ---------- Utilities ---------- */
                function uid(prefix = 't') {
                    return prefix + '-' + Math.random().toString(36).slice(2, 9);
                }

                function getUserInitials(name) {
                    if (!name) return 'U';
                    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
                }

                /* ---------- Add / Edit tasks ---------- */
                const modalOverlay = document.getElementById('modalOverlay');
                const modalTitleInput = document.getElementById('modalTitleInput');
                const modalProject = document.getElementById('modalProject');
                const modalAssignee = document.getElementById('modalAssignee');
                const modalColumn = document.getElementById('modalColumn');
                const modalDue = document.getElementById('modalDue');
                let editingTaskId = null;

                document.getElementById('closeModal').addEventListener('click', closeModal);
                document.getElementById('modalCancel').addEventListener('click', function() {
                    // Don't reset project selection on cancel
                    closeModal();
                });

                document.getElementById('modalSave').addEventListener('click', () => {
                    const title = (modalTitleInput.value || '').trim();
                    if (!title) {
                        alert('Please provide a title');
                        modalTitleInput.focus();
                        return;
                    }
                    const project = modalProject.value;
                    const assignee = modalAssignee.value;
                    const column = modalColumn.value;
                    const due = modalDue.value || null;

                    if (editingTaskId) {
                        // update existing
                        const t = kanbanState.tasks.find(x => x.id == editingTaskId);
                        if (!t) return;
                        t.title = title;
                        t.project = project;
                        if (assignee && !t.assigned.includes(parseInt(assignee))) t.assigned.push(parseInt(assignee));
                        t.due = due;
                    } else {
                        // new
                        const newTask = {
                            id: uid('t'),
                            number: '', // Will be set by backend
                            title,
                            column,
                            project,
                            projectName: projectsData[project] || '',
                            tags: [],
                            assigned: assignee ? [parseInt(assignee)] : [],
                            assignedNames: assignee ? [usersData[assignee]] : [],
                            due
                        };
                        // insert into state.tasks
                        kanbanState.tasks.push(newTask);
                    }
                    closeModal();
                    renderBoard();

                    // TODO: Save to backend
                    // saveTask(title, project, assignee, column, due);
                });

                function closeModal() {
                    editingTaskId = null;
                    modalOverlay.classList.add('hidden');
                }

                /* ---------- Inline popovers for assign/due ---------- */
                const inlinePopover = document.getElementById('inlinePopover');
                const popoverContent = document.getElementById('popoverContent');
                document.getElementById('popoverCancel').addEventListener('click', () => inlinePopover.classList.add('hidden'));

                function openAssignPopover(taskId, anchorEl) {
                    const rect = anchorEl.getBoundingClientRect();
                    inlinePopover.style.top = (rect.bottom + 8) + 'px';
                    inlinePopover.style.left = (rect.left) + 'px';
                    popoverContent.innerHTML = `
    <div class="text-sm font-medium mb-2">Assign user</div>
    <div class="space-y-2">
      ${Object.entries(usersData).map(([id, name]) => `<div><label class="inline-flex items-center gap-2"><input type="checkbox" name="assign_user_${taskId}" value="${id}" /> <span class="text-sm">${name}</span></label></div>`).join('')}
      <div class="mt-2 flex justify-end"><button id="assignSave" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm">Assign</button></div>
    </div>
  `;
                    inlinePopover.classList.remove('hidden');
                    document.getElementById('assignSave').onclick = function() {
                        const selected = Array.from(document.querySelectorAll(
                            `input[name="assign_user_${taskId}"]:checked`)).map(el => parseInt(el.value));
                        const t = kanbanState.tasks.find(x => x.id == taskId);
                        if (t) t.assigned = selected;
                        inlinePopover.classList.add('hidden');
                        renderBoard();

                        // TODO: Save to backend
                        // saveTaskAssignment(taskId, selected);
                    };
                }

                function openDuePopover(taskId, anchorEl) {
                    const rect = anchorEl.getBoundingClientRect();
                    inlinePopover.style.top = (rect.bottom + 8) + 'px';
                    inlinePopover.style.left = (rect.left) + 'px';
                    popoverContent.innerHTML = `
    <div class="text-sm font-medium mb-2">Set due date</div>
    <input id="popoverDate" type="date" class="w-full border rounded-md px-2 py-2" />
    <div class="mt-2 flex justify-between">
      <button id="popoverClear" class="px-3 py-1 text-sm rounded-md bg-slate-100">Clear</button>
      <button id="popoverSave2" class="px-3 py-1 text-sm rounded-md bg-blue-600 text-white">Save</button>
    </div>
  `;
                    // Set current due date if exists
                    const t = kanbanState.tasks.find(x => x.id == taskId);
                    if (t && t.due) {
                        document.getElementById('popoverDate').value = t.due;
                    }
                    inlinePopover.classList.remove('hidden');
                    document.getElementById('popoverSave2').onclick = function() {
                        const d = document.getElementById('popoverDate').value || null;
                        const t = kanbanState.tasks.find(x => x.id == taskId);
                        if (t) t.due = d;
                        inlinePopover.classList.add('hidden');
                        renderBoard();

                        // TODO: Save to backend
                        // saveTaskDueDate(taskId, d);
                    };
                    document.getElementById('popoverClear').onclick = function() {
                        const t = kanbanState.tasks.find(x => x.id == taskId);
                        if (t) t.due = null;
                        inlinePopover.classList.add('hidden');
                        renderBoard();

                        // TODO: Save to backend
                        // saveTaskDueDate(taskId, null);
                    };
                }
                window.openAssignPopover = openAssignPopover;
                window.openDuePopover = openDuePopover;

                /* ---------- Misc: Close inline popover if clicking elsewhere ---------- */
                document.addEventListener('click', function(e) {
                    if (inlinePopover && !inlinePopover.classList.contains('hidden')) {
                        if (!e.target.closest('#inlinePopover')) inlinePopover.classList.add('hidden');
                    }
                });

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
                        kanbanState.tasks = newTasksData.map(task => ({
                            id: task.id,
                            number: task.task_number ? task.task_number : '',
                            title: task.title,
                            column: task.status,
                            project: task.project_id,
                            projectName: task.project ? task.project.name : '',
                            tags: task.tags || [],
                            assigned: task.taskAssignee ? task.taskAssignee.map(a => a.id) : [],
                            assignedNames: task.taskAssignee ? task.taskAssignee.map(a => a.name) : [],
                            due: task.due_date || null,
                            priority: task.priority || null
                        }));
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
        </script>
    @endpush
</x-filament-panels::page>
