<?php


namespace GECU\Http;


use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{

    /**
     * @inheritDoc
     */
    public function createServerRequest(
      string $method,
      $uri,
      array $serverParams = []
    ): ServerRequestInterface {
        // TODO: Implement createServerRequest() method.
    }
}
