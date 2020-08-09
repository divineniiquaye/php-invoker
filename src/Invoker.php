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
use DivineNii\Invoker\Interfaces\ParameterResolverInterface;
use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Invoke a callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Invoker extends ResolverChain implements Interfaces\InvokerInterface
{
    /**
     * @var CallableResolver
     */
    private $callableResolver;

    /**
     * @var ParameterResolverInterface
     */
    private $parameterResolver;

    /**
     * @param callable[]         $resolvers
     * @param ContainerInterface $container
     */
    public function __construct(array $resolvers = [], ContainerInterface $container = null)
    {
        parent::__construct($container);

        $this->callableResolver  = new CallableResolver($container);
        $this->parameterResolver = new ParameterResolver($this->createParameterResolver($resolvers));
    }

    /**
     * {@inheritdoc}
     */
    public function call($callable, array $parameters = [])
    {
        $callable           = $this->callableResolver->resolve($callable);
        $callableReflection = CallableReflection::create($callable);
        $args               = $this->parameterResolver->getParameters($callableReflection, $parameters);

        // Sort by array key because call_user_func_array ignores numeric keys
        \ksort($args);

        // Check all parameters are resolved
        $diff = \array_diff_key($callableReflection->getParameters(), $args);

        if (!empty($diff)) {
            /** @var ReflectionParameter $parameter */
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
     * @return ParameterResolverInterface By default it's a ResolverChain
     */
    public function getParameterResolver(): ParameterResolverInterface
    {
        return $this->parameterResolver;
    }

    /**
     * @return CallableResolver
     */
    public function getCallableResolver(): CallableResolver
    {
        return $this->callableResolver;
    }

    /**
     * Create the parameter resolvers.
     *
     * @param callable[] $resolvers
     *
     * @return array<int|string,mixed>
     */
    private function createParameterResolver(array $resolvers): array
    {
        return \array_merge(
            [
                [$this, 'resolveNumericArray'],
                [$this, 'resolveTypeHint'],
                [$this, 'resolveAssociativeArray'],
                [$this, 'resolveDefaultValue'],
                [$this, 'resolveParameterContainer'],
            ],
            $resolvers
        );
    }
}
