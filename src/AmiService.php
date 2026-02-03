<?php

namespace Khody2012\LaravelAmiToolkit;

class AmiService
{
    protected AmiClient $client;

    public function __construct(AmiClient $client)
    {
        $this->client = $client;
    }
    public function connect()
    {
        return $this->client->connect();
    }

    /**
     * Send a ping command to AMI.
     *
     * @return mixed
     */
    public function ping()
    {
        return $this->client->action('Ping');
    }

    public function originate(array $params)
    {
        return $this->client->action('Originate', $params,true);
    }

}
