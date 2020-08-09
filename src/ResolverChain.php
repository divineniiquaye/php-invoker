<?php

declare(strict_types=1);

/*
 * This file is part of PHP Invoker.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DivineNii\Invoker;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;

class ResolverChain
{
    /** @var null|ContainerInterface */
    private $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return null|ContainerInterface
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * Tries to map an associative array (string-indexed) to the parameter names.
     *
     * E.g. `->call($callable, ['foo' => 'bar'])` will inject the string `'bar'`
     * in the parameter named `$foo`.
     *
     * Parameters that are not indexed by a string are ignored.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array<int|string,mixed>    $providedParameters
     * @param array<int|string,mixed>    $resolvedParameters
     *
     * @return array<int|mixed>
     */
    public function resolveAssociativeArray(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $parameters = \array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $name  = $parameter->name;

            // Inject entries from a DI container using the parameter names.
            if ($name && (null !== $this->container && $this->container->has($name))) {
                $resolvedParameters[$index] = $this->container->get($name);

                continue;
            }

            if (\array_key_exists($name, $providedParameters)) {
                $resolvedParameters[$index] = $providedParameters[$name];
            }
        }

        return $resolvedParameters;
    }

    /**
     * Simply returns all the values of the $providedParameters array that are
     * indexed by the parameter position (i.e. a number).
     *
     * E.g. `->call($callable, ['foo', 'bar'])` will simply resolve the parameters
     * to `['foo', 'bar']`.
     *
     * Parameters that are not indexed by a number (i.e. parameter position)
     * will be ignored.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array<int|string,mixed>    $providedParameters
     * @param array<int|string,mixed>    $resolvedParameters
     *
     * @return array<int|mixed>
     */
    public function resolveNumericArray(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        // Skip parameters already resolved
        $providedParameters = \array_diff_key($providedParameters, $resolvedParameters) ?? $providedParameters;

        foreach ($providedParameters as $key => $value) {
            if (\is_int($key)) {
                $resolvedParameters[$key] = $value;
            }
        }

        return $resolvedParameters;
    }

    /**
     * Finds the default value for a parameter, *if it exists*.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array<int|string,mixed>    $providedParameters
     * @param array<int|string,mixed>    $resolvedParameters
     *
     * @return array<int|mixed>
     */
    public function resolveDefaultValue(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $parameters = \array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            /** @var ReflectionParameter $parameter */
            if ($parameter->isOptional() || $parameter->isDefaultValueAvailable()) {
                try {
                    $resolvedParameters[$index] = $parameter->getDefaultValue();
                } catch (ReflectionException $e) {
                    // Can't get default values from PHP internal classes and functions
                }
            }
        }

        return $resolvedParameters;
    }

    /**
     * Inject entries using type-hints.
     *
     * Tries to match type-hints with the parameters provided.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array<int|string,mixed>    $providedParameters
     * @param array<int|string,mixed>    $resolvedParameters
     *
     * @return array<int|mixed>
     */
    public function resolveTypeHint(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $parameters = \array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $parameterType = $parameter->getType();

            if (!$parameterType instanceof ReflectionType) {
                // No type
                continue;
            }

            if ($parameterType->isBuiltin()) {
                // Primitive types are not supported
                continue;
            }

            // @codeCoverageIgnoreStart
            if (!$parameterType instanceof ReflectionNamedType) {
                // Union types are not supported
                continue;
            }
            // @codeCoverageIgnoreEnd

            $parameterClass = $parameterType->getName();

            // Inject entries from a DI container using the type-hints.
            if (null !== $this->container && $this->container->has($parameterClass)) {
                $resolvedParameters[$index] = $this->container->get($parameterClass);

                continue;
            }

            if (\array_key_exists($parameterClass, $providedParameters)) {
                $resolvedParameters[$index] = $providedParameters[$parameterClass];
            }
        }

        return $resolvedParameters;
    }

    /**
     * Inject or create a class instance from a DI container or return existing instance
     * from $providedParameters using the type-hints.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param array<int|string,mixed>    $providedParameters
     * @param array<int|string,mixed>    $resolvedParameters
     *
     * @return array<int|mixed>
     */
    public function resolveParameterContainer(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $parameters = \array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $parameterClass = $parameter->getClass();

            if (!$parameterClass instanceof ReflectionClass) {
                continue;
            }

            // Inject entries from a DI container using the type-hints.
            if (null !== $this->container) {
                try {
                    $resolvedParameters[$index] = $this->container->get($parameterClass->name);

                    continue;
                } catch (NotFoundExceptionInterface $e) {
                    // We need no exception thrown here
                }
            }

            // If an instance is detected
            foreach ($providedParameters as $key => $value) {
                if (\is_a($value, $parameterClass->name, true)) {
                    $resolvedParameters[$index] = $providedParameters[$key];

                    continue;
                }
            }

            if ($parameterClass->isInstantiable()) {
                $resolvedParameters[$index] = $parameterClass->newInstance();
            }
        }

        return $resolvedParameters;
    }
}
