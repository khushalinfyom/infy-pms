<div>
    <style>
        #no-results {
            display: none;
            padding: 10px 0;
            color: #a0a0a0;
        }
    </style>
    <div x-show="$store.sidebar.isOpen"
        class="flex items-center w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
           rounded-lg px-3 py-2 gap-2 shadow-sm focus-within:ring-2 focus-within:ring-primary-500">
        @svg('phosphor-magnifying-glass', ['class' => 'sm:w-5 sm:h-5 w-4 h-4 text-gray-700 dark:text-gray-400'])

        <input id="sidebar-search" type="search" placeholder="Search"
            class="w-full bg-transparent border-none focus:ring-0 outline-none text-sm text-gray-900 dark:text-gray-200"
            onkeyup="attachSearchEvent()" oninput="attachSearchEvent()" />
    </div>


    <span id="no-results">
        No matching records found
    </span>

    <script>
        function attachSearchEvent() {
            const searchInput = document.getElementById('sidebar-search');
            const noResultsDiv = document.getElementById('no-results');
            const menuItems = document.querySelectorAll('.fi-sidebar-item');
            const sidebarGroups = document.querySelectorAll('.fi-sidebar-group');

            const query = searchInput.value.toLowerCase();
            let found = false;

            menuItems.forEach(function(item) {
                const label = item.querySelector('.fi-sidebar-item-label');
                if (label && label.textContent.toLowerCase().includes(query)) {
                    item.style.display = '';
                    found = true;
                } else {
                    item.style.display = 'none';
                }
            });

            sidebarGroups.forEach(function(group) {
                const visibleItems = group.querySelectorAll('.fi-sidebar-item:not([style*="display: none"])');
                group.style.display = visibleItems.length > 0 ? '' : 'none';
            });

            noResultsDiv.style.display = found ? 'none' : 'block';

            if (query === '') {
                menuItems.forEach(item => item.style.display = '');
                sidebarGroups.forEach(group => group.style.display = '');
                noResultsDiv.style.display = 'none';
            }
        }
    </script>
</div>
