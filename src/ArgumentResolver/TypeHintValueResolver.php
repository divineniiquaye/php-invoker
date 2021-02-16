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

/**
 * Inject entries using type-hints or create a class instance.
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
    public function resolve(\ReflectionParameter $parameter, array $providedParameters)
    {
        $paramType = $parameter->getType();
        $types  = $paramType instanceof \ReflectionUnionType ? $paramType->getTypes() : [$paramType];
        $result = [];

        foreach ($types as $parameterType) {
            if (!$parameterType instanceof \ReflectionNamedType || $parameterType->isBuiltin()) {
                // No type, Primitive types are not supported
                return null;
            }
            $paramName = $parameterType->getName();

            if ($paramName === 'self') {
                $paramName = $parameter->getDeclaringClass()->getName();
            }

            if (\array_key_exists($paramName, $providedParameters)) {
                $result[] = $providedParameters[$paramName];
                unset($providedParameters[$paramName]);

                continue;
            }

            // If an instance is detected
            foreach ($providedParameters as $key => $value) {
                if (\is_a($value, $paramName, true)) {
                    $result[] = $providedParameters[$key];

                    continue;
                }
            }

            // Inject entries from a DI container using the type-hints.
            if (null !== $this->container) {
                try {
                    $result[] = $this->container->get($paramName);

                    continue;
                } catch (NotFoundExceptionInterface $e) {
                    // We need no exception thrown here
                }
            }

            try {
                if (\class_exists($paramName)) {
                    $result[] = new $paramName();
                }
            } catch (\ArgumentCountError $e) {
                // Throw no exception ...
            }
        }

        if ([] !== $result) {
            return $parameter->isVariadic() ? $result : end($result);
        }
    }
}
