<?php

namespace Khody2012\LaravelAmiToolkit;

use Exception;

class AmiClient
{
    protected $socket;
    protected $host;
    protected $port;
    protected $username;
    protected $secret;
    protected $timeout ;
    protected bool $connected = false;

    public function __construct(string $host, int $port, string $username, string $secret, int $timeout)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->secret = $secret;
        $this->timeout = $timeout;
    }

    protected function ensureConnected()
    {
        if (!$this->connected) {
            $this->connect();
        }
    }

    public function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            throw new Exception("Unable to connect to AMI: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Greeting message
        $this->read();

        $loginMessage = $this->buildMessage('Login', [
            'Username' => $this->username,
            'Secret'   => $this->secret,
            'Events'   => 'off'
        ]);

        $this->write($loginMessage);

        $response = $this->read();

        if (strpos($response, 'Authentication accepted') === false) {
            throw new Exception("AMI Authentication failed for {$this->username}@{$this->host}:{$this->port}");

        }

        $this->connected = true;

        return true;
    }

    public function action(string $action, array $params = []): string
    {
        $this->ensureConnected();

        $message = $this->buildMessage($action, $params);
        return $this->send($message);
    }

    public function send(string $message): string
    {
        $this->ensureConnected();

        $this->write($message);
        return $this->read();
    }

    protected function write(string $data): void
    {
        fwrite($this->socket, $data);
    }

    protected function read(): string
    {
        $data = '';
        while (($line = fgets($this->socket)) !== false) {
            $data .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        if ($data === '') {
            throw new Exception("Empty response from AMI");
        }

        return $data;
    }

    protected function buildMessage(string $action, array $params = []): string
    {
        $message = "Action: $action\r\n";
        foreach ($params as $key => $value) {
            $message .= "$key: $value\r\n";
        }
        return $message . "\r\n";
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
            $this->connected = false;
        }
    }
    public function __destruct()
    {
        $this->disconnect();
    }
}
