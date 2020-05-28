<?php

namespace GECU\Http\Cors;

use GECU\Rest\Http\Request;
use GECU\Rest\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Cors implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @inheritDoc
     */
    public function process(
      ServerRequestInterface $request,
      RequestHandlerInterface $handler
    ): ResponseInterface {
        if (
          $request->getMethod() === Request::METHOD_OPTIONS
        ) {
            if ($request->hasHeader('Access-Control-Request-Method')) {
                return $this->responseFactory
                  ->createResponse(Response::STATUS_NO_CONTENT)
                  ->withHeader('Access-Control-Allow-Methods', '*')
                  ->withHeader('Access-Control-Allow-Headers', '*');
            }
        }
        return $handler->handle($request);
    }
}
