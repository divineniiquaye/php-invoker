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
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Resolves a callable from a container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CallableResolver
{
    public const CALLABLE_PATTERN = '!^([^\:]+)(:|@)([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    /**
     * @var null|ContainerInterface
     */
    private $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Resolve the given callable into a real PHP callable.
     *
     * @param array<mixed,string>|callable|string $callable
     *
     * @throws NotCallableException
     *
     * @return callable real PHP callable
     */
    public function resolve($callable)
    {
        // Shortcut for a very common use case
        if ($callable instanceof Closure) {
            return $callable;
        }

        if (\is_string($callable) && 1 === \preg_match(self::CALLABLE_PATTERN, $callable, $matches)) {
            // check for callable as "class:method", and "class@method"
            $callable = [$matches[1], $matches[3]];
        }

        // The callable is a container entry name
        if (\is_string($callable) && null !== $this->container) {
            try {
                return $this->resolve($this->container->get($callable));
            } catch (NotFoundExceptionInterface $e) {
                if ($this->container->has($callable)) {
                    throw $e;
                }

                throw NotCallableException::fromInvalidCallable($callable, true);
            }
        }

        $callable = $this->resolveFromContainer($callable);

        // Callable object or string (i.e. implementing __invoke())
        if ((\is_string($callable) || \is_object($callable)) && \method_exists($callable, '__invoke')) {
            return $this->resolve([$callable, '__invoke']);
        }

        if (!\is_callable($callable)) {
            throw NotCallableException::fromInvalidCallable($callable, null !== $this->container);
        }

        return $callable;
    }

    /**
     * @param array<mixed,string>|callable|string $callable
     *
     * @throws NotCallableException
     *
     * @return array<mixed,string>|callable|string
     */
    private function resolveFromContainer($callable)
    {
        $isStaticCallToNonStaticMethod = false;

        // If it's already a callable there is nothing to do
        if (\is_callable($callable)) {
            $isStaticCallToNonStaticMethod = $this->isStaticCallToNonStaticMethod($callable);

            if (!$isStaticCallToNonStaticMethod) {
                return $callable;
            }
        }

        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (\is_array($callable) && \is_string($callable[0])) {
            try {
                if (null !== $this->container) {
                    // Replace the container entry name by the actual object
                    $callable[0] = $this->container->get($callable[0]);
                }

                if (\is_string($callable[0]) && \class_exists($callable[0])) {
                    $callable[0] = (new ReflectionClass($callable[0]))->newInstance();
                }

                return $callable;
            } catch (Throwable $e) {
                if (null !== $this->container && $this->container->has($callable[0])) {
                    throw $e;
                }

                if ($e instanceof NotFoundExceptionInterface) {
                    if ($isStaticCallToNonStaticMethod) {
                        throw new NotCallableException(\sprintf(
                            'Cannot call %s::%s() because %2$s() is not a static method and "%1$s" is not a container entry',
                            $callable[0],
                            $callable[1]
                        ));
                    }

                    throw new NotCallableException(\sprintf(
                        'Cannot call %s on %s because it is not a class nor a valid container entry',
                        $callable[1],
                        $callable[0]
                    ));
                }

                throw NotCallableException::fromInvalidCallable($callable, null !== $this->container);
            }
        }

        // Unrecognized stuff, we let it fail later
        return $callable;
    }

    /**
     * Check if the callable represents a static call to a non-static method.
     *
     * @param mixed $callable
     *
     * @return bool
     */
    private function isStaticCallToNonStaticMethod($callable)
    {
        if (\is_array($callable) && \is_string($callable[0])) {
            list($class, $method) = $callable;
            $reflection           = new ReflectionMethod($class, $method);

            return !$reflection->isStatic();
        }

        return false;
    }
}
