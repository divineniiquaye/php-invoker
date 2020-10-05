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

use ReflectionParameter;

/**
 * Responsible for resolving the value of an argument based on its arguments.
 *
 * @author Iltar van der Berg <kjarli@gmail.com>
 */
interface ArgumentValueResolverInterface
{
    /**
     * Returns the possible value(s).
     *
     * @param reflectionParameter     $parameter          - The ReflectionParameter object of a callable
     * @param array<int|string,mixed> $providedParameters
     *
     * @return mixed
     */
    public function resolve(ReflectionParameter $parameter, array $providedParameters);
}
