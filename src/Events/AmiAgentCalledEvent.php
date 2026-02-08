<?php

namespace Khody2012\LaravelAmiToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmiAgentCalledEvent
{
    use Dispatchable, SerializesModels;

    public string $uniqueId;
    public string $queue;
    public string $agentCalled;
    public string $agentName;
    public string $channelCalling;
    public ?string $callerIdNum;

    public function __construct(array $data)
    {
        $this->uniqueId     = $data['Uniqueid']     ?? '';
        $this->queue        = $data['Queue']        ?? '';
        $this->agentCalled  = $data['AgentCalled']  ?? '';
        $this->agentName    = $data['AgentName']    ?? '';
        $this->channelCalling = $data['ChannelCalling'] ?? '';
        $this->callerIdNum  = $data['CallerIDNum']  ?? null;
    }
}