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

use DivineNii\Invoker\CallableResolver;
use DivineNii\Invoker\Exceptions\InvocationException;
use DivineNii\Invoker\Exceptions\NotCallableException;
use DivineNii\Invoker\Tests\Fixtures\BlankClassMagic;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/**
 * CallableResolverTest
 */
class CallableResolverTest extends TestCase
{
    public function testConstructor(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new CallableResolver($container);

        $this->assertInstanceOf(CallableResolver::class, $factory);
    }

    public function testNotResolveWithStatic(): void
    {
        $factory = new CallableResolver();

        $this->expectExceptionMessage(
            '\'DivineNii\\\\Invoker\\\\Tests\\\\Fixtures\\\\BlankClass::staticNotMethod\'' .
            ' is neither a callable nor a valid container entry'
        );
        $this->expectException(NotCallableException::class);

        $factory->resolve('DivineNii\Invoker\Tests\Fixtures\BlankClass::staticNotMethod');
    }

    public function testNotResolveWithString(): void
    {
        $factory = new CallableResolver();

        $this->expectExceptionMessage('\'handler\' is neither a callable nor a valid container entry');
        $this->expectException(NotCallableException::class);

        $factory->resolve('handler');
    }

    public function testNotResolveWithArray(): void
    {
        $factory = new CallableResolver();

        $this->expectExceptionMessage('DivineNii\Invoker\Tests\Fixtures\BlankClass::none() is not a callable.');
        $this->expectException(NotCallableException::class);

        $factory->resolve([Fixtures\BlankClass::class, 'none']);
    }

    public function testResolveWithContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('handler')->willReturn(true);
        $container->method('get')->willReturn(new Fixtures\BlankClass());

        $factory = new CallableResolver($container);

        $this->assertIsCallable($factory->resolve('handler'));
        $this->assertIsCallable($factory->resolve(['handler', 'method']));
    }

    /**
     * @dataProvider implicitContainerGets()
     *
     * @param mixed $unResolved
     */
    public function testResolveWithContainerHasAndException($unResolved): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('handler')->willReturn(true);
        $container->method('get')->willThrowException(self::notFoundException());

        $this->expectExceptionMessage('Is not a callable, yeah.');
        $this->expectException(NotFoundExceptionInterface::class);

        $factory = new CallableResolver($container);
        $factory->resolve($unResolved);
    }

    /**
     * @dataProvider implicitContainerGets()
     *
     * @param mixed $unResolved
     */
    public function testResolveWithContainerHasNotAndException($unResolved): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('handler')->willReturn(false);
        $container->method('get')->willThrowException(self::notFoundException());

        if (\is_array($unResolved)) {
            $this->expectExceptionMessage(
                'Cannot call method on handler because it is not a class nor a valid container entry'
            );
        } else {
            $this->expectExceptionMessage('\'handler\' is neither a callable nor a valid container entry');
        }
        $this->expectException(NotCallableException::class);

        $factory = new CallableResolver($container);
        $factory->resolve($unResolved);
    }

    public function testResolveWithContainerHasNotAndStaticException(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(Fixtures\BlankClassMagic::class)->willReturn(false);
        $container->method('get')->willThrowException(self::notFoundException());

        $this->expectExceptionMessage(
            'Cannot call DivineNii\Invoker\Tests\Fixtures\BlankClassMagic::staticMethod() because staticMethod() ' .
            'is not a static method and "DivineNii\Invoker\Tests\Fixtures\BlankClassMagic" is not a container entry'
        );
        $this->expectException(NotCallableException::class);

        $factory = new CallableResolver($container);
        $factory->resolve([BlankClassMagic::class, 'staticMethod']);
    }

    public function testResolveWithContainerHasNotAndMagicException(): void
    {
        $container    = $this->createMock(ContainerInterface::class);
        $newException = new class ('is not a callable, none.') extends InvocationException {
        };

        $container->method('has')->with(Fixtures\BlankClassMagic::class)->willReturn(false);
        $container->method('get')->willThrowException($newException);

        $this->expectExceptionMessage(
            'DivineNii\Invoker\Tests\Fixtures\BlankClassMagic::staticMethod() is not a callable. ' .
            'A __call() method exists but magic methods are not supported.'
        );
        $this->expectException(NotCallableException::class);

        $factory = new CallableResolver($container);
        $factory->resolve([BlankClassMagic::class, 'staticMethod']);
    }

    /**
     * @dataProvider implicitTypes
     *
     * @param mixed $unResolved
     */
    public function testResolve($unResolved): void
    {
        $factory = new CallableResolver();

        $this->assertIsCallable($factory->resolve($unResolved));
    }

    /**
     * @return Generator
     */
    public function implicitContainerGets(): Generator
    {
        yield 'String Invocable Get Type:' => [
            'handler',
        ];

        yield 'String Method Get Type:' => [
            ['handler', 'method'],
        ];
    }

    /**
     * @return Generator
     */
    public function implicitTypes(): Generator
    {
        yield 'String Invocable Class Type:' => [
            Fixtures\BlankClass::class,
        ];

        yield 'Object Invocable Class Type:' => [
            new Fixtures\BlankClass(),
        ];

        yield 'Callable Class Type:' => [
            [new Fixtures\BlankClass(), 'method'],
        ];

        yield 'Array Class Type:' => [
            [Fixtures\BlankClass::class, 'method'],
        ];

        yield 'Pattern : Class Type:' => [
            'DivineNii\Invoker\Tests\Fixtures\BlankClass:method',
        ];

        yield 'Pattern @ Class Type:' => [
            'DivineNii\Invoker\Tests\Fixtures\BlankClass@method',
        ];

        yield 'Callable String Type:' => [
            'phpinfo',
        ];

        yield 'Callable Closure Type:' => [
            function (string $something): string {
                return $something;
            },
        ];

        yield 'Callable Static String Class Type 1' => [
            'DivineNii\Invoker\Tests\Fixtures\BlankClass::staticMethod',
        ];

        yield 'Callable Static Array Class Type 1' => [
            [Fixtures\BlankClass::class, 'staticMethod'],
        ];
    }

    /**
     * @return NotFoundExceptionInterface|Throwable
     */
    public static function notFoundException()
    {
        return new class ('Is not a callable, yeah.') extends Exception implements NotFoundExceptionInterface {
        };
    }
}
