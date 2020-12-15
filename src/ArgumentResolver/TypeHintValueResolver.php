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
use ReflectionParameter;

/**
 * Inject entries using type-hints.
 * Tries to match type-hints with the parameters provided.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class TypeHintValueResolver implements ArgumentValueResolverInterface
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
    public function resolve(ReflectionParameter $parameter, array $providedParameters)
    {
        $parameterType = $parameter->getType();

        if (!$parameterType instanceof \ReflectionNamedType || $parameterType->isBuiltin()) {
            // No type, Primitive types and Union types are not supported
            return;
        }

        $parameterType = $parameterType->getName();

        // Inject entries from a DI container using the type-hints.
        if (null !== $this->container && $this->container->has($parameterType)) {
            return $this->container->get($parameterType);
        }

        if (\array_key_exists($parameterType, $providedParameters)) {
            return $providedParameters[$parameterType];
        }
    }
}
