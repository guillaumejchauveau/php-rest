<?php


namespace GECU\Rest\Route;


interface RouteProviderInterface
{
    public function getRoutes(): iterable;
}
