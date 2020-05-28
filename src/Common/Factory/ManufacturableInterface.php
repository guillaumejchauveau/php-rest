<?php


namespace GECU\Common\Factory;


/**
 * A trait for objects capable of providing a factory for themselves.
 */
interface ManufacturableInterface
{
    /**
     * @return mixed|null The factory as a pseudo callable
     * @see FactoryHelper
     */
    public static function getFactory();
}
