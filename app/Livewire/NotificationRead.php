<?php

namespace App\Livewire;

use App\Models\UserNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Livewire\Component;

class NotificationRead extends Component
{
    public string $tab = 'new';

    public function switchTab($name)
    {
        $this->tab = $name;
    }

    public function markAsRead($notificationId)
    {
        $notification = UserNotification::find($notificationId);

        if ($notification) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllAsRead()
    {

        $notification = UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        FilamentNotification::make()
            ->success()
            ->title('All notifications have been marked as read.')
            ->send();
    }

    public function render()
    {
        $newNotifications = UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $historyNotifications = UserNotification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('filament.notification.notification-read', [
            'newNotifications' => $newNotifications,
            'historyNotifications' => $historyNotifications,
        ]);
    }
}
