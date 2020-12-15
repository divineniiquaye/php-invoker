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
use DivineNii\Invoker\Exceptions\NotCallableException;
use DivineNii\Invoker\Exceptions\NotEnoughParametersException;
use DivineNii\Invoker\Interfaces\ArgumentResolverInterface;
use DivineNii\Invoker\Invoker;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use stdClass;

/**
 * InvokerTest
 */
class InvokerTest extends TestCase
{
    public function testConstructor(): void
    {
        $invoker = new Invoker();

        $this->assertNull($invoker->getContainer());
        $this->assertInstanceOf(CallableResolver::class, $invoker->getCallableResolver());
        $this->assertInstanceOf(ArgumentResolverInterface::class, $invoker->getArgumentResolver());
    }

    /**
     * @dataProvider invokableData
     *
     * @param mixed                   $callable
     * @param array<int|string,mixed> $parameters
     * @param mixed                   $matches
     */
    public function testInvoke($callable, array $parameters = [], $matches = null): void
    {
        $invoker = new Invoker();
        $result  = $invoker->call($callable, $parameters);

        $this->assertSame($matches, $result);
    }

    /**
     * @dataProvider invokableContainerData
     *
     * @param mixed $callable
     */
    public function testInvokeWithContainer($callable): void
    {
        $container = new class () implements ContainerInterface {
            /**
             * {@inheritdoc}
             */
            public function has($id)
            {
                return true;
            }

            /**
             * {@inheritdoc}
             */
            public function get($id)
            {
                return new Fixtures\BlankClass();
            }
        };

        $invoker = new Invoker([], $container);
        $result  = $invoker->call($callable);

        $this->assertSame(Fixtures\BlankClass::BODY, $result);
    }

    public function testInvokeWithConstructor(): void
    {
        $this->expectExceptionMessage(
            'DivineNii\Invoker\Tests\Fixtures\BlankClassWithArgument::__invoke() is not a callable.'
        );
        $this->expectException(NotCallableException::class);

        $invoker = new Invoker();
        $invoker->call(Fixtures\BlankClassWithArgument::class);
    }

    public function testCannotInvokeUnknownMethod(): void
    {
        $this->expectExceptionMessage('DivineNii\Invoker\Tests\Fixtures\BlankClass::none() is not a callable.');
        $this->expectException(NotCallableException::class);

        $invoker = new Invoker();
        $invoker->call([new Fixtures\BlankClass(), 'none']);
    }

    public function testCannotInvokeMagicMethod(): void
    {
        $this->expectExceptionMessage(
            'DivineNii\Invoker\Tests\Fixtures\BlankClassMagic::method() is not a callable.' .
            ' A __call() method exists but magic methods are not supported.'
        );
        $this->expectException(NotCallableException::class);

        $invoker = new Invoker();
        $invoker->call([new Fixtures\BlankClassMagic(), 'method']);
    }

    public function testInvokeParametersException(): void
    {
        $this->expectExceptionMessage(
            'Unable to invoke the callable because no value was given for parameter 2 ($bar)'
        );
        $this->expectException(NotEnoughParametersException::class);

        $invoker = new Invoker();
        $invoker->call(function ($foo, $bar, $baz) {
            return $foo . $bar . $baz;
        }, [
            'foo' => 'foo',
            'baz' => 'baz',
        ]);
    }

    public function testInvokeParametersWithNoValueException(): void
    {
        $this->expectExceptionMessage(
            'Unable to invoke the callable because no value was given for parameter 2 ($bar)'
        );
        $this->expectException(NotEnoughParametersException::class);

        $invoker = new Invoker();
        $invoker->call(function ($foo, $bar, $baz = null) {
            return $foo . $bar . $baz;
        }, [
            'foo' => 'foo',
            'baz' => 'baz',
        ]);
    }

    public function testInvokeParametersWithOptionalDefaultParameter(): void
    {
        $invoker = new Invoker();
        $result  = $invoker->call(function ($foo, $bar = null, $baz = null) {
            return $foo . $bar . $baz;
        }, [
            'foo' => 'foo',
            'baz' => 'baz',
        ]);

        $this->assertEquals('foobaz', $result);
    }

    public function testInvokeWithTypehintObjectInstance(): void
    {
        $invoker = new Invoker();
        $result  = $invoker->call(function (stdClass $foo) {
            return $foo;
        });

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testInvokeWithTypehintContainerResolver(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->with('stdClass')->willReturn($expected = new stdClass());

        $invoker = new Invoker([], $container);
        $result  = $invoker->call(function (stdClass $foo): stdClass {
            return $foo;
        });

        $this->assertSame($expected, $result);
    }

    public function testInvokeWithParameterNameContainerResolver(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->with('foo')->willReturn($expected = new stdClass());

        $invoker = new Invoker([], $container);
        $result  = $invoker->call(function ($foo) {
            return $foo;
        });

        $this->assertSame($expected, $result);
    }

    /**
     * Mixing named parameters with positioned parameters is a really bad idea.
     * When that happens, the positioned parameters have the highest priority and will
     * override named parameters in case of conflicts.
     *
     * Note that numeric array indexes ignore string indexes. In our example, the
     * 'bar' value has the position `0`, which overrides the 'foo' value.
     */
    public function testInvokeWithPositionedParametersWithHighestPriority(): void
    {
        $factory = function ($foo, $bar = 300) {
            return [$foo, $bar];
        };

        $invoker = new Invoker();
        $result  = $invoker->call($factory, ['foo' => 'foo', 'bar']);

        $this->assertEquals(['bar', 300], $result);
    }

    public function testInvokeWithNonStaticMethod(): void
    {
        $this->expectExceptionMessage('DivineNii\Invoker\Tests\Fixtures\BlankClass::foo() is not a callable.');
        $this->expectException(NotCallableException::class);

        $invoker = new Invoker();
        $invoker->call([Fixtures\BlankClass::class, 'foo']);
    }

    public function testInvokeWithCallingNonCallableWithoutContainer1(): void
    {
        $this->expectExceptionMessage("'foo' is not a callable");
        $this->expectException(NotCallableException::class);

        $invoker = new Invoker();
        $invoker->call('foo');
    }

    public function testInvokeWithCallingNonCallableWithoutContainer2(): void
    {
        $this->expectExceptionMessage('NULL is not a callable');
        $this->expectException(NotCallableException::class);

        $invoker = new Invoker();
        $invoker->call(null);
    }

    public function testInvokeWithCallingNonCallableWithContainer(): void
    {
        $this->expectExceptionMessage('NULL is neither a callable nor a valid container entry');
        $this->expectException(NotCallableException::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturn(null);

        $invoker = new Invoker([], $container);
        $invoker->call('foo');
    }

    public function testInvokeWithCallingNonCallableObject(): void
    {
        $this->expectExceptionMessage('Instance of stdClass is not a callable');
        $this->expectException(NotCallableException::class);

        $invoker = new Invoker();
        $invoker->call(new stdClass());
    }

    /**
     * @return Generator
     */
    public function invokableContainerData(): Generator
    {
        yield 'Should resolve array callable from container' => [
            ['thing-to-call', 'method'],
        ];

        yield 'Should resolve static callable from container with scope resolution syntax' => [
            'thing-to-call::staticMethod',
        ];

        yield 'Should resolve callable from container with @ syntax' => [
            'thing-to-call@method',
        ];

        yield 'Should resolve array callable from container with class name' => [
            [Fixtures\BlankClass::class, 'method'],
        ];

        yield 'Should resolve callable from container with class name and : syntax' => [
            'DivineNii\Invoker\Tests\Fixtures\BlankClass:method',
        ];

        yield 'Should resolve callable from container with class name and constructor' => [
            Fixtures\BlankClassWithArgument::class,
        ];
    }

    /**
     * @return Generator
     */
    public function invokableData(): Generator
    {
        $logger = new NullLogger();

        yield 'Should invoke closure and return value' => [
            function (): string {
                return Fixtures\BlankClass::BODY;
            },
            [],
            Fixtures\BlankClass::BODY,
        ];

        yield 'Should invoke method' => [
            [new Fixtures\BlankClass(), 'method'],
            [],
            Fixtures\BlankClass::BODY,
        ];

        yield 'Should invoke static method' => [
            [new Fixtures\BlankClass(), 'staticMethod'],
            [],
            Fixtures\BlankClass::BODY,
        ];

        yield 'Should invoke static string method' => [
            'DivineNii\Invoker\Tests\Fixtures\BlankClass::staticMethod',
            [],
            Fixtures\BlankClass::BODY,
        ];

        yield 'Should invoke static invokable method' => [
            Fixtures\BlankClass::class,
            [],
            Fixtures\BlankClass::BODY,
        ];

        yield 'Should invoke static invokable object method' => [
            new Fixtures\BlankClass(),
            [],
            Fixtures\BlankClass::BODY,
        ];

        yield 'Should invoke callable with parameters indexed by position' => [
            function ($foo, $bar) {
                return $foo . $bar;
            },
            ['foo', 'bar'],
            'foobar',
        ];

        yield 'Should invoke callable with parameters indexed by name' => [
            [Fixtures\BlankClass::class, 'methodWithTypeHintParameters'],
            ['name' => 'foo', 'logger' => $logger],
            ['foo'  => $logger],
        ];

        yield 'Should invoke callable with default value for undefined parameters' => [
            function ($foo, $bar = 'bar', $baz = null) {
                return $foo . $bar . $baz;
            },
            ['foo', 'baz' => 'baz'],
            'foobarbaz',
        ];
    }
}
