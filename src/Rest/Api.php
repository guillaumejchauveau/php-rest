<?php


namespace GECU\Rest;


use GECU\Common\Factory\FactoryHelper;
use GECU\Router\Route\Route;
use InvalidArgumentException;
use Throwable;

/**
 * Class responsible of the life cycle of a REST API.
 */
class Api
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var ArgumentResolver
     */
    protected $argumentResolver;
    /**
     * The list of all the API's routes.
     * @var Route[]
     */
    protected $routes;

    /**
     * Api constructor.
     * @param string[] $resources The class names of the resources
     * @param string $webroot The path of the API entry point's directory on the
     *  server
     * @param ContainerInterface|null $container A service container for the
     *  factories and resource actions
     */
    public function __construct(
      iterable $resources,
      string $webroot,
      ?ContainerInterface $container = null
    ) {
        $this->container = $container ?? new Container();

        $serviceArgumentValueResolver = new ServiceValueResolver($this->container);
        $argumentValueResolvers = [
          $serviceArgumentValueResolver,
          new RequestContentValueResolver(
            [$serviceArgumentValueResolver]
          ),
        ];
        array_push(
          $argumentValueResolvers,
          ...ArgumentResolver::getDefaultArgumentValueResolvers()
        );
        $this->argumentResolver = new ArgumentResolver(null, $argumentValueResolvers);
    }
    /**
     * Processes the current request.
     */
    public function run(): void
    {
        $request = RestRequest::createFromGlobals();
        try {
            $response = $this->handleRequest($request);
            $response->send();
        } catch (Throwable $e) {
            $this->handleError($e)->send();
        }
    }

    /**
     * Generates a response to a given request.
     * @param RestRequest $request
     * @return Response
     */
    protected function handleRequest(RestRequest $request): Response
    {
        try {
            $resource = FactoryHelper::createObject($request->getRoute()->getResourceClassName(), $this->argumentResolver);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
        $actionName = $request->getRoute()->getActionName();
        if ($actionName === null) {
            $response = $resource;
        } else {
            /** @var callable $action */
            $action = [$resource, $actionName];
            $arguments = $this->argumentResolver->getArguments($action);
            try {
                $response = $action(...$arguments);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }

        $response = new RestResponse($response);
        if ($request->getRoute()->getStatus() !== null) {
            $response->setStatusCode($request->getRoute()->getStatus());
        }
        return $response;
    }

    /**
     * Generates a response to a given error.
     * @param Throwable $throwable
     * @return Response
     */
    protected function handleError(Throwable $throwable): Response
    {
        if ($throwable instanceof HttpExceptionInterface) {
            return new RestResponse(
              $throwable,
              $throwable->getStatusCode(),
              $throwable->getHeaders()
            );
        }
        return new RestResponse($throwable, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
