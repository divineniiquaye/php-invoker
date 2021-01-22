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

namespace DivineNii\Invoker\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Throwable;

/**
 * The given callable is not actually callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NotCallableException extends InvocationException
{
    /**
     * @param mixed                                 $value
     * @param bool                                  $containerEntry
     * @param ContainerExceptionInterface|Throwable $previous
     *
     * @return InvocationException
     */
    public static function fromInvalidCallable($value, bool $containerEntry = false, $previous = null): InvocationException
    {
        if (\is_object($value)) {
            $message = \sprintf('Instance of %s is not a callable', \get_class($value));
        } elseif (\is_array($value) && isset($value[0], $value[1])) {
            $class   = \is_object($value[0]) ? \get_class($value[0]) : $value[0];
            $extra   = \method_exists($class, '__call') ? ' A __call() method exists but magic methods are not supported.' : '';
            $message = \sprintf('%s::%s() is not a callable.%s', $class, $value[1], $extra);
        } else {
            if ($containerEntry) {
                $message = \var_export($value, true) . ' is neither a callable nor a valid container entry';
            } else {
                $message = \var_export($value, true) . ' is not a callable';
            }
        }

        return new self($message, 0, $previous);
    }
}
