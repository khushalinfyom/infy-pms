<?php

namespace App\Filament\Infolists\Components;

use App\Models\User;
use Filament\Infolists\Components\Entry;

class ProjectUserEntry extends Entry
{
    protected string $view = 'filament.infolists.components.project-user-entry';

    protected ?User $user = null;

    public function user(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): array
    {
        $user = $this->user;

        if (! $user) {
            return [
                'name' => 'User',
                'email' => '',
                'role' => 'Member',
                'image' => '',
            ];
        }

        $role = $user->roles->first()?->name ?? 'Member';

        $image = $user->getFirstMediaUrl(User::IMAGE_PATH);
        if (empty($image)) {
            $image = "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&size=128";
        }

        return [
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $role,
            'image' => $image,
        ];
    }

    public function getImageUrl(): string
    {
        return $this->getUser()['image'];
    }
}
