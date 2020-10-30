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

namespace DivineNii\Invoker\Tests;

use DivineNii\Invoker\CallableReflection;
use DivineNii\Invoker\Exceptions\NotCallableException;
use DivineNii\Invoker\Tests\Fixtures\BlankClass;
use DivineNii\Invoker\Tests\Fixtures\BlankClassMagic;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;

/**
 * CallableReflectionTest
 */
class CallableReflectionTest extends TestCase
{
    public function testCreate(): void
    {
        $this->assertInstanceOf(
            ReflectionMethod::class,
            CallableReflection::create('DivineNii\Invoker\Tests\Fixtures\BlankClass::staticMethod')
        );

        $this->assertInstanceOf(
            ReflectionMethod::class,
            CallableReflection::create([new BlankClass(), 'method'])
        );

        $this->assertInstanceOf(
            ReflectionFunction::class,
            CallableReflection::create(function (string $test): string {
                return $test;
            })
        );

        $this->assertInstanceOf(ReflectionFunction::class, CallableReflection::create('phpinfo'));
    }

    public function testCreateCatchReflectionException(): void
    {
        $this->expectExceptionMessage(
            'DivineNii\Invoker\Tests\Fixtures\BlankClassMagic::method() is not a callable. ' .
            'A __call() method exists but magic methods are not supported.'
        );
        $this->expectException(NotCallableException::class);

        CallableReflection::create([new BlankClassMagic(), 'method']);
    }

    public function testCreateNotCallableException(): void
    {
        $this->expectExceptionMessage('handler is not a callable');
        $this->expectException(NotCallableException::class);

        CallableReflection::create('handler');
    }
}
