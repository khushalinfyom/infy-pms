<?php

namespace App\Filament\Infolists\Components;

use App\Models\Client;
use Filament\Infolists\Components\Entry;

class ClientEntry extends Entry
{
    protected string $view = 'filament.infolists.components.client-entry';

    protected $client = null;

    public function client($client): static
    {
        $this->client = $client;

        return $this;
    }

    protected function resolveClient(): ?Client
    {
        if (is_callable($this->client)) {
            $result = call_user_func($this->client, $this->getRecord());

            return $result instanceof Client ? $result : null;
        }

        return $this->client instanceof Client ? $this->client : null;
    }

    public function getClient(): array
    {
        $client = $this->resolveClient();

        if (! $client instanceof Client) {
            return [
                'name'  => 'Client',
                'email' => '',
                'image' => '',
            ];
        }

        $image = $client->getFirstMediaUrl(Client::IMAGE_PATH);

        if (empty($image)) {
            $image = "https://ui-avatars.com/api/?name=" . urlencode($client->name) . "&size=128";
        }

        return [
            'name'  => $client->name,
            'email' => $client->email,
            'image' => $image,
        ];
    }

    public function getImageUrl(): string
    {
        return $this->getClient()['image'];
    }
}
