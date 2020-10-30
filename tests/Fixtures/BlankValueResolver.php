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

namespace DivineNii\Invoker\Tests\Fixtures;

use DivineNii\Invoker\Interfaces\ArgumentValueResolverInterface;
use ReflectionClass;
use ReflectionParameter;

class BlankValueResolver implements ArgumentValueResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(ReflectionParameter $parameter, array $providedParameters)
    {
        if (!($parameterClass = $parameter->getClass()) instanceof ReflectionClass) {
            return;
        }

        if ($parameterClass->getName() === ArgumentValueResolverInterface::class) {
            return $this;
        }
    }
}
