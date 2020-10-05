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
use ReflectionException;
use ReflectionParameter;

/**
 * Gets the default value defined in the action signature when no value has been given.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class DefaultValueResolver implements ArgumentValueResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(ReflectionParameter $parameter, array $providedParameters)
    {
        if ($parameter->isOptional() || $parameter->isDefaultValueAvailable()) {
            try {
                $default = $parameter->getDefaultValue();

                return null !== $default ? $default : __CLASS__;
            } catch (ReflectionException $e) {
                // Can't get default values from PHP internal classes and functions
            }
        }
    }
}
