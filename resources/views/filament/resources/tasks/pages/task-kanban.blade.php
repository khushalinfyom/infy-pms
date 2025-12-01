<x-filament-panels::page>
    <div>
        <div>
            <!-- Top bar -->
            <div class="flex items-center gap-4 mb-4">
                <!-- Left: search -->
                <div class="flex items-center gap-2 flex-1">
                    <div class="text-sm font-semibold">Search</div>
                    <div class="flex items-center bg-white rounded-md shadow px-3 py-2 gap-2 w-full">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1016.65 16.65z" />
                        </svg>
                        <input id="searchInput" placeholder="Search tasks by title..."
                            class="w-full outline-none text-sm" />
                        <button id="clearSearch" class="text-xs text-slate-500 hover:text-slate-700">Clear</button>
                    </div>
                </div>

                <!-- Right: filters and create button -->
                <div class="flex items-center gap-3">
                    {{ $this->filterForm }}
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
                gap: -8px;
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
                max-height: 62vh;
                overflow: auto;
            }

            /* Ensure proper spacing and sizing for Kanban board */
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
            }

            /* Remove horizontal scroll from columns */
            .col-scroll {
                overflow-x: hidden;
                overflow-y: auto;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

        <script>
            // Global variable to track if board is initialized
            let isBoardInitialized = false;
            let kanbanState = {
                columns: [],
                tasks: [],
                currentProjectId: null
            };

            // Function to initialize or reinitialize the board
            function initOrReinitBoard() {
                if (!isBoardInitialized) {
                    initializeBoard();
                    isBoardInitialized = true;
                } else {
                    // Reinitialize with new data
                    refreshBoardData();
                }
            }

            // Initialize the board when Livewire updates
            document.addEventListener('DOMContentLoaded', function() {
                // Use a more reliable way to detect when Livewire has finished rendering
                setTimeout(initOrReinitBoard, 100);
            });

            // Listen for Livewire update events
            document.addEventListener('livewire:updated', function() {
                setTimeout(function() {
                    refreshBoardData();
                }, 100);
            });

            // Listen for filter changes and update the board
            function attachFilterListeners() {
                // Listen for changes on any select elements that might be filters
                const filterSelects = document.querySelectorAll('select');
                filterSelects.forEach(select => {
                    select.addEventListener('change', function() {
                        // Small delay to ensure Livewire has processed the change
                        setTimeout(function() {
                            refreshBoardData();
                        }, 150);
                    });
                });

                // Also listen for changes on any input elements that might be filters
                const filterInputs = document.querySelectorAll('input');
                filterInputs.forEach(input => {
                    if (input.type === 'text' || input.type === 'search') {
                        // For text inputs, use input event with debounce
                        let timeout;
                        input.addEventListener('input', function() {
                            clearTimeout(timeout);
                            timeout = setTimeout(function() {
                                refreshBoardData();
                            }, 300);
                        });
                    } else {
                        // For other input types (checkboxes, radio buttons, etc.)
                        input.addEventListener('change', function() {
                            setTimeout(function() {
                                refreshBoardData();
                            }, 150);
                        });
                    }
                });

                // Listen for clicks on filter buttons
                const filterButtons = document.querySelectorAll('button');
                filterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Small delay to ensure Livewire has processed the change
                        setTimeout(function() {
                            refreshBoardData();
                        }, 150);
                    });
                });
            }

            // Attach filter listeners after a short delay to ensure DOM is ready
            setTimeout(attachFilterListeners, 500);

            function initializeBoard() {
                /* ---------- Data from Backend ---------- */
                const tasksData = @json($this->tasks);
                const statusesData = @json($this->statuses);
                const usersData = @json($this->users);
                const projectsData = @json($this->projects);
                const currentProjectId = @json($this->project_id);

                /* ---------- State ---------- */
                kanbanState = {
                    columns: statusesData.map(status => ({
                        id: status.id,
                        title: status.name
                    })),
                    tasks: tasksData.map(task => ({
                        id: task.id,
                        number: task.task_number ? task.project.prefix + '-' + task.task_number : '',
                        title: task.title,
                        column: task.status,
                        project: task.project_id,
                        projectName: task.project ? task.project.name : '',
                        tags: task.tags || [],
                        assigned: task.taskAssignee ? task.taskAssignee.map(a => a.id) : [],
                        assignedNames: task.taskAssignee ? task.taskAssignee.map(a => a.name) : [],
                        due: task.due_date || null,
                        priority: task.priority || null
                    })),
                    currentProjectId: currentProjectId
                };

                /* ---------- Utilities ---------- */
                function uid(prefix = 't') {
                    return prefix + '-' + Math.random().toString(36).slice(2, 9);
                }

                function formatDue(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr + 'T00:00:00');
                    return d.toLocaleDateString();
                }

                function getUserInitials(name) {
                    if (!name) return 'U';
                    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
                }

                function avatarFor(userId) {
                    const user = Object.entries(usersData).find(([id, name]) => parseInt(id) === userId);
                    const userName = user ? user[1] : 'Unknown';
                    const initials = getUserInitials(userName);

                    // Simple color generation based on user ID
                    const colors = ['bg-rose-500', 'bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-cyan-500'];
                    const colorIndex = userId % colors.length;
                    const color = colors[colorIndex] || 'bg-slate-400';

                    return `<div class="avatar ${color}" title="${userName}">${initials}</div>`;
                }

                /* ---------- Rendering ---------- */
                const boardEl = document.getElementById('board');

                function renderBoard() {
                    if (!boardEl) return;

                    // Show loading indicator if no project selected
                    if (!kanbanState.currentProjectId) {
                        boardEl.innerHTML = '<div class="kanban-loading">Please select a project to view tasks</div>';
                        return;
                    }

                    // Show loading indicator while loading tasks
                    if (kanbanState.tasks.length === 0) {
                        boardEl.innerHTML = '<div class="kanban-loading">No tasks found for this project</div>';
                        return;
                    }

                    boardEl.innerHTML = '';
                    kanbanState.columns.forEach(col => {
                        const tasksInCol = kanbanState.tasks.filter(t => t.column == col.id);
                        const colEl = document.createElement('div');
                        colEl.className = 'bg-white rounded-md shadow p-3 flex flex-col kanban-column';
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
          <button wire:click="addTaskInStatus(${col.id})" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600">+</button>
          <button class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.5" d="M6 12h12M6 6h12M6 18h12" /></svg></button>
        </div>
      </div>

      <div class="col-scroll mb-2">
        <div id="list-${col.id}" class="space-y-2 px-1 min-h-[50px]"></div>
      </div>

      <div class="mt-auto pt-2">
        <button data-addtask="${col.id}" class="w-full text-sm border rounded-md px-3 py-2 bg-slate-50 hover:bg-slate-100">+ Add Task</button>
      </div>
    `;
                        boardEl.appendChild(colEl);

                        // fill tasks
                        const listEl = colEl.querySelector(`#list-${col.id}`);
                        tasksInCol.forEach(task => {
                            const card = document.createElement('div');
                            card.className =
                                'task bg-white rounded-md shadow-sm p-3 border border-slate-100 cursor-grab';
                            card.dataset.id = task.id;
                            card.innerHTML = `
        <div class="flex items-start justify-between gap-2">
          <div class="flex-1">
            <div class="flex items-start gap-2">
              <div class="text-xs text-slate-500 rounded-sm bg-slate-100 px-2 py-1">${task.number || ''}</div>
              <div class="text-sm font-medium">${escapeHtml(task.title)}</div>
            </div>
            <div class="mt-2 text-xs text-slate-500">
              ${task.projectName ? `<span class="inline-block px-2 py-1 bg-slate-100 rounded">${escapeHtml(task.projectName)}</span>` : ''}
            </div>
          </div>
        </div>

        <div class="mt-3 flex items-center justify-between gap-2">
          <div class="avatar-stack">
            ${(task.assigned || []).slice(0, 3).map(uid => avatarFor(uid)).join('')}
            <button data-add-user="${task.id}" class="ml-2 text-xs text-slate-500 border rounded-full w-8 h-8 flex items-center justify-center">+</button>
          </div>
          <div class="text-xs">
            ${task.due ? `<div class="text-xs text-rose-600">Due: ${formatDue(task.due)}</div>` : `<button data-add-due="${task.id}" class="text-xs px-2 py-1 border rounded-md text-slate-500">Add due</button>`}
          </div>
        </div>
      `;
                            // click to open quick edit (assign/due)
                            card.querySelector('[data-add-user]')?.addEventListener('click', (e) => {
                                e.stopPropagation();
                                openAssignPopover(task.id, e.currentTarget);
                            });
                            card.querySelector('[data-add-due]')?.addEventListener('click', (e) => {
                                e.stopPropagation();
                                openDuePopover(task.id, e.currentTarget);
                            });
                            card.addEventListener('click', () => openTaskEditModal(task.id));
                            listEl.appendChild(card);
                        });
                    });

                    // re-init sortables after render
                    initSortables();
                    updateAllCounts();
                    applyClientFilters(); // Apply client-side filters after rendering
                }

                /* ---------- helpers ---------- */
                function escapeHtml(str) {
                    if (!str) return '';
                    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                }

                function updateAllCounts() {
                    kanbanState.columns.forEach(c => {
                        const countEl = document.getElementById('count-' + c.id);
                        if (countEl) {
                            countEl.textContent = `(${kanbanState.tasks.filter(t => t.column == c.id).length})`;
                        }
                    });
                }

                /* ---------- Sortable init ---------- */
                let sortInstances = [];

                function initSortables() {
                    // destroy previous
                    sortInstances.forEach(s => s.destroy && s.destroy());
                    sortInstances = [];

                    kanbanState.columns.forEach(col => {
                        const list = document.querySelector(`#list-${col.id}`);
                        if (!list) return;
                        const s = new Sortable(list, {
                            group: 'kanban-group',
                            animation: 180,
                            ghostClass: 'card-dragging',
                            onEnd: function(evt) {
                                // find moved task id
                                const el = evt.item;
                                const id = el.dataset.id;
                                const newCol = evt.to.closest('[data-col]').dataset.col;
                                const newIndex = Array.from(evt.to.children).indexOf(el);
                                // update model: set column and reposition within column (we'll keep order but not persist index per-column)
                                const t = kanbanState.tasks.find(x => x.id == id);
                                if (!t) return;
                                t.column = newCol;
                                // reorder tasks within state.tasks so render order can be stable (put moved task near same relative position)
                                // remove and re-insert before a task at newIndex within that column
                                // build list of tasks in new column excluding moved
                                const colTasks = kanbanState.tasks.filter(x => x.column == newCol && x.id !=
                                    id);
                                colTasks.splice(newIndex, 0, t);
                                // rebuild tasks array: tasks not in new column + tasks in that column (with new order)
                                const others = kanbanState.tasks.filter(x => x.column != newCol);
                                // preserve columns ordering for others
                                kanbanState.tasks = others.concat(colTasks);
                                renderBoard();

                                // TODO: Save to backend
                                // saveTaskStatus(id, newCol);
                            }
                        });
                        sortInstances.push(s);
                    });
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

                function openTaskCreateModal(defaultCol) {
                    editingTaskId = null;
                    document.getElementById('modalTitle').textContent = 'Create Task';
                    modalTitleInput.value = '';
                    // Keep current project selection
                    modalProject.value = kanbanState.currentProjectId || '';
                    modalAssignee.value = '';
                    modalColumn.value = defaultCol || kanbanState.columns[0]?.id || '';
                    modalDue.value = '';
                    modalOverlay.classList.remove('hidden');
                    modalTitleInput.focus();
                }

                function openTaskEditModal(taskId) {
                    const t = kanbanState.tasks.find(x => x.id == taskId);
                    if (!t) return;
                    editingTaskId = taskId;
                    document.getElementById('modalTitle').textContent = 'Edit Task';
                    modalTitleInput.value = t.title;
                    modalProject.value = t.project || kanbanState.currentProjectId || '';
                    modalAssignee.value = '';
                    modalColumn.value = t.column;
                    modalDue.value = t.due || '';
                    modalOverlay.classList.remove('hidden');
                    modalTitleInput.focus();
                }

                function closeModal() {
                    editingTaskId = null;
                    modalOverlay.classList.add('hidden');
                }

                /* ---------- Quick add & column add buttons ---------- */
                document.addEventListener('click', (e) => {
                    const add = e.target.closest('[data-addtask]');
                    if (add) {
                        const col = add.dataset.addtask;
                        openTaskCreateModal(col);
                    }
                });

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

                /* ---------- Client-side Filtering ---------- */
                const searchInput = document.getElementById('searchInput');

                // Add event listeners for client-side search
                if (searchInput) {
                    searchInput.addEventListener('input', applyClientFilters);
                }

                const clearSearchBtn = document.getElementById('clearSearch');
                if (clearSearchBtn) {
                    clearSearchBtn.addEventListener('click', () => {
                        if (searchInput) {
                            searchInput.value = '';
                            applyClientFilters();
                        }
                    });
                }

                function applyClientFilters() {
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

                /* ---------- Misc: Close inline popover if clicking elsewhere ---------- */
                document.addEventListener('click', function(e) {
                    if (inlinePopover && !inlinePopover.classList.contains('hidden')) {
                        if (!e.target.closest('#inlinePopover')) inlinePopover.classList.add('hidden');
                    }
                });

                /* ---------- Refresh data function ---------- */
                window.refreshBoardData = function() {
                    try {
                        // Update data from backend
                        const newTasksData = @json($this->tasks);
                        const newStatusesData = @json($this->statuses);
                        const newCurrentProjectId = @json($this->project_id);

                        // Update state
                        kanbanState.columns = newStatusesData.map(status => ({
                            id: status.id,
                            title: status.name
                        }));

                        kanbanState.tasks = newTasksData.map(task => ({
                            id: task.id,
                            number: task.task_number ? task.project.prefix + '-' + task.task_number : '',
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

                        kanbanState.currentProjectId = newCurrentProjectId;

                        // Re-render board
                        renderBoard();
                    } catch (error) {
                        console.error('Error refreshing board data:', error);
                    }
                }

                /* ---------- initial rendering ---------- */
                renderBoard();
            }

            // Make refresh function globally accessible
            window.refreshBoardData = function() {
                try {
                    // Update data from backend by re-evaluating the PHP variables
                    // This ensures we get the latest data after filter changes
                    const newTasksData = @json($this->tasks);
                    const newStatusesData = @json($this->statuses);
                    const newCurrentProjectId = @json($this->project_id);
                    const newUsersData = @json($this->users);
                    const newProjectsData = @json($this->projects);

                    // Update state
                    kanbanState.columns = newStatusesData.map(status => ({
                        id: status.id,
                        title: status.name
                    }));

                    kanbanState.tasks = newTasksData.map(task => ({
                        id: task.id,
                        number: task.task_number ? task.project.prefix + '-' + task.task_number : '',
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

                    kanbanState.currentProjectId = newCurrentProjectId;

                    // Update the users and projects data as well
                    usersData = newUsersData;
                    projectsData = newProjectsData;

                    // Re-render board
                    const boardEl = document.getElementById('board');
                    if (boardEl) {
                        // Show loading indicator if no project selected
                        if (!kanbanState.currentProjectId) {
                            boardEl.innerHTML = '<div class="kanban-loading">Please select a project to view tasks</div>';
                            return;
                        }

                        // Show loading indicator while loading tasks
                        if (kanbanState.tasks.length === 0) {
                            boardEl.innerHTML = '<div class="kanban-loading">No tasks found for this project</div>';
                            return;
                        }

                        boardEl.innerHTML = '';
                        kanbanState.columns.forEach(col => {
                            const tasksInCol = kanbanState.tasks.filter(t => t.column == col.id);
                            const colEl = document.createElement('div');
                            colEl.className = 'bg-white rounded-md shadow p-3 flex flex-col kanban-column';
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
          <button data-quick-add="${col.id}" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600">+</button>
          <button class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.5" d="M6 12h12M6 6h12M6 18h12" /></svg></button>
        </div>
      </div>

      <div class="col-scroll mb-2">
        <div id="list-${col.id}" class="space-y-2 px-1 min-h-[50px]"></div>
      </div>

      <div class="mt-auto pt-2">
        <button data-addtask="${col.id}" class="w-full text-sm border rounded-md px-3 py-2 bg-slate-50 hover:bg-slate-100">+ Add Task</button>
      </div>
    `;
                            boardEl.appendChild(colEl);

                            // fill tasks
                            const listEl = colEl.querySelector(`#list-${col.id}`);
                            tasksInCol.forEach(task => {
                                const card = document.createElement('div');
                                card.className =
                                    'task bg-white rounded-md shadow-sm p-3 border border-slate-100 cursor-grab';
                                card.dataset.id = task.id;
                                card.innerHTML = `
        <div class="flex items-start justify-between gap-2">
          <div class="flex-1">
            <div class="flex items-start gap-2">
              <div class="text-xs text-slate-500 rounded-sm bg-slate-100 px-2 py-1">${task.number || ''}</div>
              <div class="text-sm font-medium">${escapeHtml(task.title)}</div>
            </div>
            <div class="mt-2 text-xs text-slate-500">
              ${task.projectName ? `<span class="inline-block px-2 py-1 bg-slate-100 rounded">${escapeHtml(task.projectName)}</span>` : ''}
            </div>
          </div>
        </div>

        <div class="mt-3 flex items-center justify-between gap-2">
          <div class="avatar-stack">
            ${(task.assigned || []).slice(0, 3).map(uid => avatarFor(uid)).join('')}
            <button data-add-user="${task.id}" class="ml-2 text-xs text-slate-500 border rounded-full w-8 h-8 flex items-center justify-center">+</button>
          </div>
          <div class="text-xs">
            ${task.due ? `<div class="text-xs text-rose-600">Due: ${formatDue(task.due)}</div>` : `<button data-add-due="${task.id}" class="text-xs px-2 py-1 border rounded-md text-slate-500">Add due</button>`}
          </div>
        </div>
      `;
                                // click to open quick edit (assign/due)
                                card.querySelector('[data-add-user]')?.addEventListener('click', (e) => {
                                    e.stopPropagation();
                                    openAssignPopover(task.id, e.currentTarget);
                                });
                                card.querySelector('[data-add-due]')?.addEventListener('click', (e) => {
                                    e.stopPropagation();
                                    openDuePopover(task.id, e.currentTarget);
                                });
                                card.addEventListener('click', () => openTaskEditModal(task.id));
                                listEl.appendChild(card);
                            });
                        });

                        // re-init sortables after render
                        initSortables();
                        updateAllCounts();
                        applyClientFilters(); // Apply client-side filters after rendering
                    }
                } catch (error) {
                    console.error('Error refreshing board data:', error);
                }
            };

            // Helper functions that need to be accessible
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
                            // find moved task id
                            const el = evt.item;
                            const id = el.dataset.id;
                            const newCol = evt.to.closest('[data-col]').dataset.col;
                            const newIndex = Array.from(evt.to.children).indexOf(el);
                            // update model: set column and reposition within column (we'll keep order but not persist index per-column)
                            const t = kanbanState.tasks.find(x => x.id == id);
                            if (!t) return;
                            t.column = newCol;
                            // reorder tasks within state.tasks so render order can be stable (put moved task near same relative position)
                            // remove and re-insert before a task at newIndex within that column
                            // build list of tasks in new column excluding moved
                            const colTasks = kanbanState.tasks.filter(x => x.column == newCol && x.id !=
                                id);
                            colTasks.splice(newIndex, 0, t);
                            // rebuild tasks array: tasks not in new column + tasks in that column (with new order)
                            const others = kanbanState.tasks.filter(x => x.column != newCol);
                            // preserve columns ordering for others
                            kanbanState.tasks = others.concat(colTasks);

                            // Re-render board
                            window.refreshBoardData();

                            // TODO: Save to backend
                            // saveTaskStatus(id, newCol);
                        }
                    });
                    window.sortInstances.push(s);
                });
            }

            function updateAllCounts() {
                kanbanState.columns.forEach(c => {
                    const countEl = document.getElementById('count-' + c.id);
                    if (countEl) {
                        countEl.textContent = `(${kanbanState.tasks.filter(t => t.column == c.id).length})`;
                    }
                });
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function formatDue(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr + 'T00:00:00');
                return d.toLocaleDateString();
            }
        </script>
    @endpush
</x-filament-panels::page>
