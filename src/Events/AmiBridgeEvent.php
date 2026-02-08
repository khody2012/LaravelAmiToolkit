<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiBridgeEvent
{
    use Dispatchable, SerializesModels;

    public ?string $bridgeUniqueid;
    public string $channel;
    public string $linkedId;
    public string $bridgestate;
    public string $bridgetype;

    public function __construct(array $data)
    {
        $this->bridgeUniqueid = $data['BridgeUniqueid'] ?? null;
        $this->channel        = $data['Channel']        ?? '';
        $this->linkedId       = $data['Linkedid']       ?? $data['Uniqueid'] ?? '';
        $this->bridgestate    = $data['Bridgestate']    ?? 'Link';
        $this->bridgetype     = $data['Bridgetype']     ?? 'core';
    }
}