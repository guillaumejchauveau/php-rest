<?php

namespace GECU\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Authenticate implements MiddlewareInterface
{

    /**
     * @inheritDoc
     */
    public function process(
      ServerRequestInterface $request,
      RequestHandlerInterface $handler
    ): ResponseInterface {
        // TODO: Implement process() method.
    }
}
