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

use Closure;
use DivineNii\Invoker\Exceptions\NotCallableException;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Create a reflection object from a callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CallableReflection
{
    /**
     * @param callable|string $callable
     *
     * @throws NotCallableException
     *
     * @return ReflectionFunctionAbstract
     */
    public static function create($callable): ReflectionFunctionAbstract
    {
        // Standard function and closure
        if ((\is_string($callable) && \function_exists($callable)) || $callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (\is_string($callable) && \strpos($callable, '::') !== false) {
            $callable = \explode('::', $callable, 2);
        }

        if (!\is_callable($callable)) {
            throw new NotCallableException(\sprintf(
                '%s is not a callable',
                \is_string($callable) ? $callable : 'Instance of ' . \get_class($callable)
            ));
        }

        [$class, $method] = $callable;

        try {
            return new ReflectionMethod($class, $method);
        } catch (ReflectionException $e) {
            throw NotCallableException::fromInvalidCallable($callable);
        }
    }
}
