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

namespace DivineNii\Invoker\ArgumentResolver;

use DivineNii\Invoker\Interfaces\ArgumentValueResolverInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionParameter;

/**
 * Inject or create a class instance from a DI container or return existing instance
 * from $providedParameters using the type-hints.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ClassValueResolver implements ArgumentValueResolverInterface
{
    /** @var ContainerInterface|null */
    private $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ReflectionParameter $parameter, array $providedParameters)
    {
        $parameterClass = $parameter->getClass();

        if (!$parameterClass instanceof ReflectionClass) {
            return;
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
        foreach ($providedParameters as $key => $value) {
            if (\is_a($value, $parameterClass->name, true)) {
                return $providedParameters[$key];
            }
        }

        return $parameterClass->isInstantiable() ? $parameterClass->newInstance() : null;
    }
}
