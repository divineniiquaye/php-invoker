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

use ReflectionFunctionAbstract;
use ReflectionMethod;

class BlankClassWithArgument
{
    /** @var BlankClass */
    private $class;

    public function __construct(BlankClass $class)
    {
        $this->class = $class;
    }

    public function __invoke(): string
    {
        return 'sample text';
    }

    public static function method(ReflectionMethod $method): ReflectionFunctionAbstract
    {
        return $method;
    }
}
