<div>
    @php
        $notificationsIcon = 'heroicon-s-bell';

        function getNotificationIcon($title)
        {
            return match ($title) {
                'New Task Assigned' => 'heroicon-o-clipboard-document-list',
                'New Project Assigned' => 'heroicon-o-briefcase',
                'Removed From Project' => 'heroicon-o-user-minus',
                'New Invoice Created' => 'heroicon-o-document-text',
                'Project Status Changed' => 'heroicon-o-arrow-path',
                'New User Assigned to Project' => 'heroicon-o-user-plus',
                default => 'heroicon-o-bell',
            };
        }
    @endphp

    <style>
        .fi-modal-header.fi-sticky {
            padding: 15px 22px !important;
        }

        .fi-modal-content {
            padding-top: 15px !important;
        }
    </style>
    <x-filament::modal class="modal-color" width="md" slide-over sticky-footer sticky-header
        badge="{{ count($newNotifications) }}">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#fdf3c6] dark:bg-[#4e350e]">
                    <x-dynamic-component :component="$notificationsIcon"
                        class="w-5 h-5 text-[#e17100] fill-[#e17100] dark:text-[#f8ba00] dark:fill-[#f8ba00]" />
                </div>

                <span class="text-lg font-semibold">Notifications
                </span>
            </div>
        </x-slot>
        <x-slot name="trigger">
            <x-filament::button icon="heroicon-s-bell" size="" outlined>
                @if (count($newNotifications) != 0)
                    <x-slot name="badge">
                        {{ count($newNotifications) }}
                    </x-slot>
                @endif
            </x-filament::button>
        </x-slot>

        <div class="border-b border-gray-300 dark:border-gray-700 mb-3 flex gap-6">
            <button wire:click="switchTab('new')"
                class="pb-2 text-sm font-medium
        {{ $tab === 'new' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-primary-600' }}">
                Unread
            </button>

            <button wire:click="switchTab('history')"
                class="pb-2 text-sm font-medium
        {{ $tab === 'history'
            ? 'text-primary-600 border-b-2 border-primary-600'
            : 'text-gray-500 hover:text-primary-600' }}">
                All
            </button>
        </div>


        @if ($tab === 'new')
            @foreach ($newNotifications as $notification)
                <div class="flex items-start gap-4 p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
               rounded-xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
                    wire:click="markAsRead({{ $notification->id }})">

                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#fdf3c6] dark:bg-[#4e350e]">
                        <x-dynamic-component :component="getNotificationIcon($notification->title)"
                            class="w-5 h-5 text-[#e17100] fill-[#e17100] dark:text-[#f8ba00] dark:fill-[#f8ba00]" />
                    </div>

                    <div class="flex-1">

                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $notification->title }}
                            </h3>

                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                            </span>
                        </div>

                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ $notification->description ?? 'No description available.' }}
                        </p>

                    </div>

                </div>
            @endforeach

            @if ($newNotifications->isEmpty())
                <div class="text-center text-gray-500 py-8 font-semibold text-lg">No New Notifications.</div>
            @endif
        @endif
        @if ($tab === 'history')
            @foreach ($historyNotifications as $notification)
                <div
                    class="flex items-start gap-4 p-4 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm
                {{ $notification->read_at ? 'bg-white dark:bg-gray-800' : 'bg-[#fffae6] dark:bg-[#5f513c]' }}
            ">

                    <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#fdf3c6] dark:bg-[#4e350e]">
                        <x-dynamic-component :component="getNotificationIcon($notification->title)"
                            class="w-5 h-5 text-[#e17100] fill-[#e17100] dark:text-[#f8ba00] dark:fill-[#f8ba00]" />
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $notification->title }}
                            </h3>

                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">
                            {{ $notification->description }}
                        </p>
                    </div>
                </div>
            @endforeach
            @if ($historyNotifications->isEmpty())
                <div class="text-center text-gray-500 py-8 font-semibold text-lg">
                    No Notifications.
                </div>
            @endif
        @endif

        <x-slot name="footer">
            <div class="custom-notify">
                <x-filament::link wire:click="markAllAsRead" :disabled="count($newNotifications) == 0">
                    Mark All As Read
                </x-filament::link>
            </div>
        </x-slot>
    </x-filament::modal>
</div>
