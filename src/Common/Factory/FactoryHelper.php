<?php


namespace GECU\Common\Factory;


use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;

/**
 * Contains utility functions for factories. Supported factory type is a
 * super-set of callable (pseudo callable): an array callable can have a
 * combination of a class name and a non-static method or the constructor. In
 * that case, the class will be instantiated before invoking the factory.
 * A pseudo callable can also be a FQSEN string
 * ({@link https://docs.phpdoc.org/latest/glossary.html#term-fqsen})
 * representing a function (including method).
 */
class FactoryHelper
{
    /**
     * Computes the return type of a given factory using reflectivity. If the
     * type is self, the actual type is computed based on the factory
     * definition.
     * @param $factory mixed Pseudo callable ({@see FactoryHelper})
     * @return string|null The computed return type of the factory or null if it
     *  cannot be determined
     */
    public static function getFactoryReturnType($factory): ?string
    {
        if (is_string($factory)) {
            $factory = self::parsePseudoCallableFqsen($factory);
        }
        try {
            if (is_array($factory)) {
                $reflection = new ReflectionMethod($factory[0], $factory[1]);
            } elseif (is_object($factory) && !$factory instanceof Closure) {
                $reflection = (new ReflectionObject($factory))->getMethod('__invoke');
            } else {
                $reflection = new ReflectionFunction($factory);
            }
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException('Invalid factory', 0, $e);
        }
        $returnTypeReflection = $reflection->getReturnType();
        if (!$returnTypeReflection instanceof ReflectionNamedType) {
            return null;
        }
        $returnType = $returnTypeReflection->getName();
        if ($returnType === 'self') {
            if ($factory instanceof Closure) {
                $returnType = Closure::class;
            } elseif (is_array($factory) || is_object($factory)) {
                $returnType = $reflection->getDeclaringClass()->getName();
            } else {
                return null;
            }
        }
        return $returnType;
    }

    /**
     * Transforms a function or method string representation into a pseudo
     * callable ({@see FactoryHelper}).
     * @param string $fqsen A string representing a function or method
     * @return mixed The pseudo callable corresponding to the FQSEN
     */
    public static function parsePseudoCallableFqsen(string $fqsen)
    {
        if (function_exists($fqsen)) {
            return $fqsen;
        }
        if (strpos($fqsen, '::') === false) {
            throw new InvalidArgumentException('Invalid pseudo callable FQSEN');
        }
        [$class, $method] = explode('::', $fqsen, 2);
        $method = str_replace('()', '', $method);
        return [$class, $method];
    }

    /**
     * Invokes a factory and returns the result. Determines the arguments for
     * the factory and, if necessary, the factory class constructor
     * ({@see FactoryHelper}) using an argument resolver.
     * @param $factory mixed Pseudo callable ({@see FactoryHelper})
     * @param ArgumentResolver $argumentResolver
     * @return mixed The result of the factory
     */
    public static function invokeFactory(
      $factory,
      ArgumentResolver $argumentResolver
    ) {
        if (is_string($factory)) {
            $factory = self::parsePseudoCallableFqsen($factory);
        }
        $factoryArgs = $argumentResolver->getArguments($factory);
        if (!is_array($factory)) {
            return $factory(...$factoryArgs);
        }
        try {
            $reflection = new ReflectionMethod($factory[0], $factory[1]);
            // Factory is the class' constructor.
            if ($reflection->isConstructor()) {
                return $reflection->getDeclaringClass()->newInstanceArgs(
                  $factoryArgs
                );
            }
            // Factory requires a class instantiation first.
            if (!$reflection->isStatic() && !is_object($factory[0])) {
                $factory[0] = self::createObject($factory[0], $argumentResolver);
            }
            return $factory(...$factoryArgs);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException('Invalid factory', 0, $e);
        }
    }

    /**
     * @param string $className
     * @param ArgumentResolver $argumentResolver
     * @return mixed
     */
    public static function createObject(string $className, ArgumentResolver $argumentResolver)
    {
        if (is_a($className, ManufacturableInterface::class, true)) {
            /** @var ManufacturableInterface $className */
            $factory = $className::getFactory();
        } else {
            $factory = [$className, '__construct'];
        }
        return self::invokeFactory($factory, $argumentResolver);
    }
}
