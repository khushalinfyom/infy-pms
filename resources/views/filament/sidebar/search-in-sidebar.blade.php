<div>
    <style>
        #no-results {
            display: none;
            padding: 10px 0;
            color: #a0a0a0;
        }
    </style>

    <x-filament::input.wrapper x-show="$store.sidebar.isOpen">
        <x-slot name="prefix">
            <x-filament::icon icon="heroicon-s-magnifying-glass" />
        </x-slot>
        <x-filament::input type="search" class="searchinsidebar" id="sidebar-search" placeholder="Search"
            onkeyup="attachSearchEvent()" oninput="attachSearchEvent()" />
    </x-filament::input.wrapper>

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
