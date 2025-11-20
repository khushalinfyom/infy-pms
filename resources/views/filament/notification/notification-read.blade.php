<div>
    @php
        $notificationsIcon = count($notifications) > 0 ? 'heroicon-s-bell' : 'heroicon-s-bell-slash';

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

    <x-filament::modal class="modal-color" width="md" slide-over icon="{{ $notificationsIcon }}" alignment="center"
        sticky-footer sticky-header badge="{{ count($notifications) }}">
        @if ($notifications->isEmpty())
            <x-slot name="heading">
                No Notifications
            </x-slot>
            <x-slot name="description">
                You don't have any new notification
            </x-slot>
        @else
            <x-slot name="heading">
                Notifications
            </x-slot>
        @endif
        <x-slot name="trigger">
            <x-filament::button icon="heroicon-s-bell" size="" outlined>
                @if (count($notifications) != 0)
                    <x-slot name="badge">
                        {{ count($notifications) }}
                    </x-slot>
                @endif
            </x-filament::button>
        </x-slot>

        @foreach ($notifications as $notification)
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

        <x-slot name="footer">
            <div class="custom-notify">
                <x-filament::link wire:click="markAllAsRead" :disabled="count($notifications) == 0">
                    Mark All As Read
                </x-filament::link>
            </div>
        </x-slot>
    </x-filament::modal>
</div>
