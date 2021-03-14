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

use DivineNii\Invoker\Exceptions\NotEnoughParametersException;
use DivineNii\Invoker\Interfaces\ArgumentResolverInterface;
use DivineNii\Invoker\Interfaces\ArgumentValueResolverInterface;
use Psr\Container\ContainerInterface;

/**
 * Invoke a callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Invoker implements Interfaces\InvokerInterface
{
    /** @var CallableResolver */
    private $callableResolver;

    /** @var ArgumentResolverInterface */
    private $argumentResolver;

    /** @var null|ContainerInterface */
    private $container;

    /**
     * @param iterable<ArgumentValueResolverInterface> $argumentValueResolvers
     * @param null|ContainerInterface                  $container
     */
    public function __construct(iterable $argumentValueResolvers = [], ?ContainerInterface $container = null)
    {
        $this->container         = $container;
        $this->callableResolver  = new CallableResolver($container);
        $this->argumentResolver  = new ArgumentResolver($argumentValueResolvers, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function call($callable, array $parameters = [])
    {
        $callable           = $this->callableResolver->resolve($callable);
        $callableReflection = CallableReflection::create($callable);
        $args               = $this->argumentResolver->getParameters($callableReflection, $parameters);

        // Sort by array key because call_user_func_array ignores numeric keys
        \ksort($args);

        // Check all parameters are resolved
        $diff = \array_diff_key($callableReflection->getParameters(), $args);

        if (!empty($diff)) {
            /** @var \ReflectionParameter $parameter */
            $parameter = \reset($diff);

            throw new NotEnoughParametersException(\sprintf(
                'Unable to invoke the callable because no value was given for parameter %d ($%s)',
                $parameter->getPosition() + 1,
                $parameter->name
            ));
        }

        return \call_user_func_array($callable, $args);
    }

    /**
     * {@inheritdoc}
     */
    public function getArgumentResolver(): ArgumentResolverInterface
    {
        return $this->argumentResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallableResolver(): CallableResolver
    {
        return $this->callableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }
}
