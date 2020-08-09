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
use ReflectionFunctionAbstract;

class ParameterResolver implements ParameterResolverInterface
{
    /** @var callable[]
     */
    private $resolvers;

    /**
     * @param callable[] $resolvers
     */
    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    /**
     * Push a parameter resolver after the ones already registered.
     *
     * @param callable $resolver
     */
    public function appendResolver(callable $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * Insert a parameter resolver before the ones already registered.
     *
     * @param callable $resolver
     */
    public function prependResolver(callable $resolver): void
    {
        \array_unshift($this->resolvers, $resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(ReflectionFunctionAbstract $reflection, array $providedParameters = []): array
    {
        $reflectionParameters = $reflection->getParameters();
        $resolvedParameters   = [];

        foreach ($this->resolvers as $resolver) {
            $resolvedParameters = ($resolver)($reflection, $providedParameters, $resolvedParameters);

            $diff = \array_diff_key($reflectionParameters, $resolvedParameters);

            if (empty($diff)) {
                // Stop traversing: all parameters are resolved
                return $resolvedParameters;
            }
        }

        return $resolvedParameters;
    }
}
