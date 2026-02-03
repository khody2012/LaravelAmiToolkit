<?php

namespace Khody2012\LaravelAmiToolkit\Commands;

use Khody2012\LaravelAmiToolkit\AmiService;
use Exception;

/**
 * Class OriginateCall
 *
 * Executes the Originate action on AMI to initiate a call.
 */
class OriginateCall
{
    protected AmiService $amiService;

    protected array $params = [];

    /**
     * OriginateCall constructor.
     *
     * @param AmiService $amiService
     */
    public function __construct(AmiService $amiService)
    {
        $this->amiService = $amiService;
    }

    /**
     * Set the parameters for the originate call.
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Execute the Originate command.
     *
     * @return string
     * @throws Exception
     */
    public function execute(): string
    {
        if (empty($this->params)) {
            throw new Exception("Originate parameters are not set.");
        }

        return $this->amiService->originate($this->params);
    }
}
