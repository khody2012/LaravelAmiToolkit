<?php

namespace Khody2012\LaravelAmiToolkit;

use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\EventLoop\Loop;
use RuntimeException;

class AmiConnection
{
    protected $config;
    protected $loop;
    protected $connector;
    protected $connection;
    protected $isConnected = false;
    protected $buffer = '';
    protected $pendingActions = [];

    public function __construct(array $config, LoopInterface $loop = null)
    {
        $this->config = $config;
        $this->loop = $loop ?? Loop::get();
        $this->connector = new Connector($this->loop);
    }

    public function connect(): PromiseInterface
    {
        $uri = "tcp://{$this->config['host']}:{$this->config['port']}";

        return $this->connector->connect($uri)->then(function (ConnectionInterface $conn) {
            $this->connection = $conn;
            $this->isConnected = true;
            $this->setupHandlers();
            $this->login();
            Log::info("AMI connected to {$this->config['host']}:{$this->config['port']}");
            return $this;
        }, function ($err) {
            Log::error("AMI connect failed: " . $err->getMessage());
            throw $err;
        });
    }

    protected function login(): void
    {
        $this->sendAction('Login', [
            'Username' => $this->config['username'],
            'Secret' => $this->config['secret'],
            'Events' => 'on',
        ], function ($response) {
            if (stripos($response['Response'] ?? '', 'Success') === false) {
                throw new RuntimeException("AMI Login failed");
            }
            Log::info('AMI Login successful');
        });
    }

    public function sendAction(string $action, array $params = [], ?callable $onResponse = null): PromiseInterface
    {
        return $this->ensureConnected()->then(function () use ($action, $params, $onResponse) {
            $actionId = uniqid('act_', true);
            $params['ActionID'] = $actionId;

            $message = $this->buildMessage($action, $params);

            $this->connection->write($message);

            if ($onResponse) {
                $this->pendingActions[$actionId] = $onResponse;
            }

            return \React\Promise\resolve(true);
        });
    }

    public function ensureConnected(): PromiseInterface
    {
        if ($this->isConnected && $this->connection !== null) {
            return \React\Promise\resolve($this);
        }

        Log::info('اتصال AMI وجود ندارد، در حال اتصال مجدد...');

        return $this->connect();
    }

    protected function setupHandlers(): void
    {
        $this->connection->on('data', function ($chunk) {
            $this->buffer .= $chunk;
            $this->processBuffer();
        });

        $this->connection->on('close', fn() => $this->onDisconnect('closed'));
        $this->connection->on('error', fn($err) => $this->onDisconnect($err->getMessage()));
    }

    protected function processBuffer(): void
    {
        while (($pos = strpos($this->buffer, "\r\n\r\n")) !== false) {
            $raw = substr($this->buffer, 0, $pos);
            $this->buffer = substr($this->buffer, $pos + 4);

            $event = $this->parseRaw($raw);

            if ($event) {
                if (isset($event['ActionID']) && isset($this->pendingActions[$event['ActionID']])) {
                    $callback = $this->pendingActions[$event['ActionID']];
                    unset($this->pendingActions[$event['ActionID']]);
                    $callback($event);
                } elseif (isset($event['Event'])) {
                    event(new \Khody2012\LaravelAmiToolkit\Events\AmiRawEvent($event));
                }
            }
        }
    }

    protected function parseRaw(string $raw): ?array
    {
        $lines = explode("\r\n", $raw);
        $data = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) continue;
            [$k, $v] = explode(':', $line, 2);
            $data[trim($k)] = trim($v);
        }
        return $data ?: null;
    }

    protected function buildMessage(string $action, array $params): string
    {
        $msg = "Action: $action\r\n";
        foreach ($params as $k => $v) {
            $msg .= "$k: $v\r\n";
        }
        return $msg . "\r\n";
    }

    protected function onDisconnect(string $reason): void
    {
        $this->isConnected = false;
        $this->connection = null;
        Log::warning("AMI disconnected: $reason");

        $this->loop->addTimer(5, function () {
            $this->connect()->otherwise(function ($e) {
                Log::error("Reconnect failed: " . $e->getMessage());
            });
        });
    }

    public function disconnect(): void
    {
        $this->connection?->end();
    }
}