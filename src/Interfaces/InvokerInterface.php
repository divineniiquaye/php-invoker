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

namespace DivineNii\Invoker\Interfaces;

use DivineNii\Invoker\CallableResolver;
use Psr\Container\ContainerInterface;
use DivineNii\Invoker\Exceptions\InvocationException;
use DivineNii\Invoker\Exceptions\NotCallableException;
use DivineNii\Invoker\Exceptions\NotEnoughParametersException;

/**
 * Invoke a callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface InvokerInterface
{
    /**
     * Call the given function using the given parameters.
     *
     * @param array<mixed,string>|callable|string $callable   function to call
     * @param array<int|string,mixed>             $parameters parameters to use
     *
     * @throws InvocationException          base exception class for all the sub-exceptions below
     * @throws NotCallableException
     * @throws NotEnoughParametersException
     *
     * @return mixed result of the function
     */
    public function call($callable, array $parameters = []);

    /**
     * Gets the Argument resolver.
     *
     * @return ArgumentResolverInterface
     */
    public function getArgumentResolver(): ArgumentResolverInterface;

    /**
     * Gets the Calable resolver.
     *
     * @return CallableResolver
     */
    public function getCallableResolver(): CallableResolver;

    /**
     * Gets the PSR-11 container instance
     *
     * @return null|ContainerInterface
     */
    public function getContainer(): ?ContainerInterface;
}
