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
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
            ' is not a callable'
        );
        $this->expectException(NotCallableException::class);

        $factory->resolve('DivineNii\Invoker\Tests\Fixtures\BlankClass::staticNotMethod');
    }

    public function testNotResolveWithString(): void
    {
        $factory = new CallableResolver();

        $this->expectExceptionMessage('\'handler\' is not a callable');
        $this->expectException(NotCallableException::class);

        $factory->resolve('handler');
    }

    public function testNotResolveWithArray(): void
    {
        $factory = new CallableResolver();

        $this->assertIsCallable($factory->resolve([Fixtures\BlankClass::class, 'method']));
    }

    public function testNotResolveWithArrayWithException(): void
    {
        $factory = new CallableResolver();

        $this->expectExceptionMessage('DivineNii\Invoker\Tests\Fixtures\BlankClass::none() is not a callable.');
        $this->expectException(NotCallableException::class);

        $factory->resolve([Fixtures\BlankClass::class, 'none']);
    }

    public function testNotResolveWithObject(): void
    {
        $factory = new CallableResolver();

        $this->expectExceptionMessage(
            'Instance of DivineNii\Invoker\Tests\Fixtures\BlankClassMagic is not a callable'
        );
        $this->expectException(NotCallableException::class);

        $factory->resolve(new Fixtures\BlankClassMagic());
    }

    public function testResolveWithStaticMethod(): void
    {
        $factory = new CallableResolver();

        $this->assertIsCallable($factory->resolve([new Fixtures\BlankClass(), 'staticMethod']));
        $this->assertIsCallable($factory->resolve([Fixtures\BlankClass::class, 'staticMethod']));
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

        if (\is_array($unResolved) || 'handler@method' === $unResolved) {
            $this->expectExceptionMessage('handler::method() is not a callable.');
            $this->expectException(NotCallableException::class);
        } else {
            $this->expectExceptionMessage('\'handler\' is neither a callable nor a valid container entry');
            $this->expectException(NotCallableException::class);
        }

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

        if (\is_array($unResolved) || 'handler@method' === $unResolved) {
            $this->expectExceptionMessage('handler::method() is not a callable.');
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

        $exceptionMessage = 'Cannot call DivineNii\Invoker\Tests\Fixtures\BlankClassMagic::staticMethod() because ' .
        'staticMethod() is not a static method and "DivineNii\Invoker\Tests\Fixtures\BlankClassMagic';

        if (\PHP_VERSION_ID >= 80000) {
            $exceptionMessage = 'DivineNii\Invoker\Tests\Fixtures\BlankClassMagic::staticMethod() is not a callable.' .
            ' A __call() method exists but magic methods are not supported.';
        }

        $this->expectExceptionMessage($exceptionMessage);
        $this->expectException(NotCallableException::class);

        $factory = new CallableResolver($container);
        $factory->resolve([BlankClassMagic::class, 'staticMethod']);
    }

    /**
     * @dataProvider implicitMagicData
     *
     * @param bool|string $hasContainer
     */
    public function testResolveWithContainerAndMagicException($hasContainer): void
    {
        static $container;

        if (!\is_string($hasContainer)) {
            $container = $this->createMock(ContainerInterface::class);

            $container->method('has')->with(Fixtures\BlankClassMagic::class)->willReturn($hasContainer);
            $container->method('get')->willThrowException(new InvocationException('is not a callable, none.'));
        }

        $factory = new CallableResolver($container);

        try {
            $callable = $factory->resolve([Fixtures\BlankClassMagic::class, 'method']);

            // Will not throw an exception if $hasContainer is bool
            $this->assertIsCallable($callable);
        } catch (NotCallableException $e) {
            $this->assertEquals(
                'DivineNii\Invoker\Tests\Fixtures\BlankClassMagic::method() is not a callable. ' .
                'A __call() method exists but magic methods are not supported.',
                $e->getMessage()
            );

            $this->assertInstanceOf(InvocationException::class, $prev = $e->getPrevious());
            $this->assertEquals('is not a callable, none.', $prev->getMessage());
        }
    }

    public function testResolveWithContainerHasNotException(): void
    {
        $container    = $this->createMock(ContainerInterface::class);
        $newException = new class ('is not a callable, none.') extends InvocationException {
        };

        $container->method('has')->willReturn(false);
        $container->method('get')->willThrowException($newException);

        $this->expectExceptionMessage('\'handler\' is not a callable');
        $this->expectException(NotCallableException::class);

        $factory = new CallableResolver($container);

        try {
            $factory->resolve(['handler', 'noneMethod']);
        } catch (NotCallableException $e) {
            $this->assertSame('handler::noneMethod() is not a callable.', $e->getMessage());

            throw NotCallableException::fromInvalidCallable('handler');
        }
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
     * @return \Generator
     */
    public function implicitContainerGets(): \Generator
    {
        yield 'String Invocable Get Type:' => [
            'handler',
        ];

        yield 'String Method Get Type:' => [
            ['handler', 'method'],
        ];

        yield 'String Method Get Type with @ Seperator:' => [
            'handler@method',
        ];
    }

    /**
     * @return \Generator
     */
    public function implicitMagicData(): \Generator
    {
        yield ['container'];

        yield [false];

        yield [true];
    }

    /**
     * @return \Generator
     */
    public function implicitTypes(): \Generator
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
     * @return NotFoundExceptionInterface|\Throwable
     */
    public static function notFoundException()
    {
        return new class ('Is not a callable, yeah.') extends \Exception implements NotFoundExceptionInterface {
        };
    }
}
