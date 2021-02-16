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

use DivineNii\Invoker\ArgumentResolver\DefaultValueResolver;
use DivineNii\Invoker\ArgumentResolver\NamedValueResolver;
use DivineNii\Invoker\ArgumentResolver\TypeHintValueResolver;
use DivineNii\Invoker\Interfaces\ArgumentResolverInterface;
use DivineNii\Invoker\Interfaces\ArgumentValueResolverInterface;
use Psr\Container\ContainerInterface;

class ArgumentResolver implements ArgumentResolverInterface
{
    /** @var ArgumentValueResolverInterface[] */
    private $argumentValueResolvers;

    /** @var null|ContainerInterface */
    private static $container;

    /**
     * @param iterable<ArgumentValueResolverInterface> $argumentValueResolvers
     * @param null|ContainerInterface                  $container
     */
    public function __construct(iterable $argumentValueResolvers = [], ?ContainerInterface $container = null)
    {
        self::$container              = $container;
        $this->argumentValueResolvers = $argumentValueResolvers ?: self::getDefaultArgumentValueResolvers();
    }

    /**
     * Push a parameter resolver after the ones already registered.
     *
     * @param ArgumentValueResolverInterface $resolvers
     */
    public function appendResolver(ArgumentValueResolverInterface ...$resolvers): void
    {
        foreach ($resolvers as $resolver) {
            $this->argumentValueResolvers[] = $resolver;
        }
    }

    /**
     * Insert a parameter resolver before the ones already registered.
     *
     * @param ArgumentValueResolverInterface $resolvers
     */
    public function prependResolver(ArgumentValueResolverInterface ...$resolvers): void
    {
        \array_unshift($this->argumentValueResolvers, ...$resolvers);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(\ReflectionFunctionAbstract $reflection, array $providedParameters = []): array
    {
        $resolvedParameters   = [];
        $reflectionParameters = $reflection->getParameters();

        foreach ($reflectionParameters as $parameter) {
            $position = $parameter->getPosition();

            foreach ($this->argumentValueResolvers as $resolver) {
                if (null !== $resolved = $resolver->resolve($parameter, $providedParameters)) {
                    if ($resolved === DefaultValueResolver::class) {
                        $resolved = null;
                    }

                    if ($parameter->isVariadic() && \is_array($resolved)) {
                        if (\count($resolved) > 1) {
                            foreach (\array_chunk($resolved, 1) as $index => [$value]) {
                                $resolvedParameters[$index + 1] = $value;
                            }

                            continue;
                        }

                        $resolved = \current($resolved);
                    }

                    $resolvedParameters[$position] = $resolved;
                }

                if (empty(\array_diff_key($reflectionParameters, $resolvedParameters))) {
                    // Stop traversing: all parameters are resolved
                    return $resolvedParameters;
                }
            }
        }

        return $resolvedParameters;
    }

    /**
     * @return iterable<ArgumentValueResolverInterface>
     */
    public static function getDefaultArgumentValueResolvers(): iterable
    {
        return [
            new NamedValueResolver(self::$container),
            new TypeHintValueResolver(self::$container),
            new DefaultValueResolver(),
        ];
    }
}
