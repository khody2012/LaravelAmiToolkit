<?php

namespace Khody2012\LaravelAmiToolkit\Commands;

use Khody2012\LaravelAmiToolkit\AmiService;
use React\Promise\PromiseInterface;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Console\Command;;
/**
 * Class OriginateCall
 *
 * Fluent builder & executor for AMI Originate action (async).
 */
class OriginateCall extends Command
{
    protected AmiService $amiService;
    protected array $params = [];

    public function __construct(AmiService $amiService)
    {
        $this->amiService = $amiService;
    }

    /**
     * Set parameters (fluent)
     */
    public function setParams(array $params): self
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * Required: Channel to originate from (e.g. SIP/1000)
     */
    public function channel(string $channel): self
    {
        $this->params['Channel'] = $channel;
        return $this;
    }

    /**
     * Required if using extension: Extension, Context, Priority
     */
    public function extension(string $exten, string $context, int $priority = 1): self
    {
        $this->params['Exten']    = $exten;
        $this->params['Context']  = $context;
        $this->params['Priority'] = $priority;
        return $this;
    }

    /**
     * Optional: Async mode (recommended for non-blocking)
     */
    public function async(bool $async = true): self
    {
        $this->params['Async'] = $async ? 'ture' : 'false';
        return $this;
    }

    /**
     * Execute the originate action asynchronously
     *
     * @return PromiseInterface<array|null>  // response or null if fireAndForget
     * @throws InvalidArgumentException
     */
    public function execute(bool $fireAndForget = false): PromiseInterface
    {
        $this->validateParams();

        return $this->amiService->originate($this->params, $fireAndForget);
    }

    protected function validateParams(): void
    {
        if (empty($this->params['Channel'])) {
            throw new InvalidArgumentException('Channel is required for Originate');
        }

        $hasExten = !empty($this->params['Exten']);
        $hasApp   = !empty($this->params['Application']);

        if (!$hasExten && !$hasApp) {
            throw new InvalidArgumentException(
                'Originate requires either Exten+Context+Priority or Application+Data'
            );
        }

        if ($hasExten && (empty($this->params['Context']) || empty($this->params['Priority']))) {
            throw new InvalidArgumentException('Context and Priority required when using Exten');
        }
    }

    /**
     * Convenience: Execute and wait for result (sync-like in blocking context)
     * Warning: Blocks the event loop – use only in console/commands, not web
     */
    public function executeAndWait(): ?array
    {
        $promise = $this->execute(false);

        return $promise->wait();
    }
}