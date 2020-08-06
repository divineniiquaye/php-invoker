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

use ReflectionFunctionAbstract;

/**
 * Resolves the parameters to use to call the callable.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface ParameterResolverInterface
{
    /**
     * Resolves the parameters to use to call the callable.
     *
     * @param ReflectionFunctionAbstract $reflection         reflection object for the callable
     * @param array<int|string,mixed>    $providedParameters parameters provided by the caller
     *
     * @return array
     */
    public function getParameters(ReflectionFunctionAbstract $reflection, array $providedParameters = []): array;
}
