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

use DivineNii\Invoker\Interfaces\ParameterResolverInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionParameter;

class ParameterResolver implements ParameterResolverInterface
{
    /** @var null|ContainerInterface */
    private $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(ReflectionFunctionAbstract $reflection, array $providedParameters = []): array
    {
        return \array_map(
            function (ReflectionParameter $parameter) use ($providedParameters) {
                $parameterClass = $parameter->getClass();
                $parameterName  = $parameter->name;

                /*
                 * Tries to map an associative array (string-indexed) to the parameter names.
                 *
                 * E.g. `->getParameters($callable, ['foo' => 'bar'])` will inject the string `'bar'`
                 * in the parameter named `$foo`.
                 */
                if (\array_key_exists($parameterName, $providedParameters)) {
                    return $providedParameters[$parameterName];
                }

                /*
                 * Inject entries from a DI container using the parameter names.
                 */
                if ($parameterName && (null !== $this->container && $this->container->has($parameterName))) {
                    return $this->container->get($parameterName);
                }

                /*
                 * Inject or create a class instance from a DI container or return existing instance
                 * from $providedParameters using the type-hints.
                 */
                if ($parameterClass && $parameter->getType()) {
                    // Tries to match type-hints with the parameters provided.
                    if (\array_key_exists($parameterClass->name, $providedParameters)) {
                        return $providedParameters[$parameterClass->name];
                    }

                    // Inject entries from a DI container using the type-hints.
                    if (null !== $this->container) {
                        try {
                            return $this->container->get($parameterClass->name);
                        } catch (NotFoundExceptionInterface $e) {
                            // We need no exception thrown here
                        }
                    }

                    // If an instance is detected
                    foreach ($providedParameters as $index => $value) {
                        if (\is_a($value, $parameterClass->name, true)) {
                            return $providedParameters[$index];
                        }
                    }

                    if ($parameterClass->isInstantiable()) {
                        return $parameterClass->newInstance();
                    }
                }

                /*
                 * Finds the default value for a parameter, *if it exists*.
                 */
                if ($parameter->isDefaultValueAvailable() || $parameter->isOptional()) {
                    try {
                        return $parameter->getDefaultValue();
                    } catch (ReflectionException $e) {
                        // Can't get default values from PHP internal classes and functions
                    }
                }

                /*
                 * Simply returns all the values of the $providedParameters array that are
                 * indexed by the parameter position (i.e. a number) or null.
                 *
                 * E.g. `->call($callable, ['foo', 'bar'])` will simply resolve the parameters
                 * to `['foo', 'bar']`.
                 *
                 * Parameters that are not indexed by a number (i.e. parameter position)
                 * will return null.
                 */
                return $providedParameters[$parameter->getPosition()] ?? null;
            },
            $reflection->getParameters()
        );
    }
}
