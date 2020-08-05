<?php

declare(strict_types=1);

/*
 * This file is part of Flight Routing.
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

use DivineNii\Invoker\Exception\InvocationException;
use DivineNii\Invoker\Exception\NotCallableException;
use DivineNii\Invoker\Exception\NotEnoughParametersException;

/**
 * Invoke a callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface InvokerInterface
{
    /**
     * Call the given function using the given parameters.
     *
     * @param callable $callable   function to call
     * @param array    $parameters parameters to use
     *
     * @throws InvocationException          base exception class for all the sub-exceptions below
     * @throws NotCallableException
     * @throws NotEnoughParametersException
     *
     * @return mixed result of the function
     */
    public function call($callable, array $parameters = []);
}
