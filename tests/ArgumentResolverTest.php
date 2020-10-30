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
use DivineNii\Invoker\Interfaces\ArgumentResolverInterface;
use DivineNii\Invoker\Invoker;
use DivineNii\Invoker\ArgumentResolver;
use DivineNii\Invoker\Interfaces\ArgumentValueResolverInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionFunctionAbstract;

/**
 * ArgumentResolverTest
 */
class ArgumentResolverTest extends TestCase
{
    public function testConstructor(): void
    {
        $factory = new ArgumentResolver();

        $this->assertInstanceOf(ArgumentResolverInterface::class, $factory);
    }

    /**
     * @dataProvider implicitCallableData
     *
     * @param callable                $callable
     * @param array<int|string,mixed> $parameters
     * @param array<int|string,mixed> $matches
     */
    public function testGetParameters(callable $callable, array $parameters = [], array $matches = []): void
    {
        $resolver           = (new Invoker())->getArgumentResolver();
        $callableReflection = $this->createCallableReflection($callable);

        $this->assertSame($matches, $resolver->getParameters($callableReflection, $parameters));
    }

    public function testGetParametersWithInstantiable(): void
    {
        $resolver           = (new Invoker())->getArgumentResolver();
        $callableReflection = $this->createCallableReflection(
            function (NullLogger $logger): LoggerInterface {
                return $logger;
            }
        );

        $resolved = $resolver->getParameters($callableReflection, []);
        $this->assertInstanceOf(LoggerInterface::class, \current($resolved));
    }

    public function testGetParametersWithInstantiableFound(): void
    {
        $resolver           = (new Invoker())->getArgumentResolver();
        $callableReflection = $this->createCallableReflection(
            function (NullLogger $logger): LoggerInterface {
                return $logger;
            }
        );

        $resolved = $resolver->getParameters(
            $callableReflection,
            [LoggerInterface::class => new NullLogger()]
        );
        $this->assertInstanceOf(LoggerInterface::class, \current($resolved));
    }

    /**
     * @dataProvider implicitCallableContainerData
     *
     * @param callable $callable
     */
    public function testGetParametersWithContainer(callable $callable): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn(new NullLogger());

        $resolver           = (new Invoker([], $container))->getArgumentResolver();
        $callableReflection = $this->createCallableReflection($callable);

        $resolved = $resolver->getParameters($callableReflection, []);
        $this->assertInstanceOf(LoggerInterface::class, \current($resolved));
        $this->assertInstanceOf(NullLogger::class, \current($resolved));
    }

    public function testGetParametersWithContainerHasFalse(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willReturn(new NullLogger());

        $resolver           = (new Invoker([], $container))->getArgumentResolver();
        $callableReflection = $this->createCallableReflection(
            [new Fixtures\BlankClass(), 'methodWithTypeHintParameter']
        );

        $resolved = $resolver->getParameters($callableReflection, []);
        $this->assertInstanceOf(LoggerInterface::class, \current($resolved));
        $this->assertInstanceOf(NullLogger::class, \current($resolved));
    }

    public function testGetParametersWithContainerHasFalseException(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willThrowException(CallableResolverTest::notFoundException());

        $resolver           = (new Invoker([], $container))->getArgumentResolver();
        $callableReflection = $this->createCallableReflection(
            function (NullLogger $logger): LoggerInterface {
                return $logger;
            }
        );

        $resolved = $resolver->getParameters($callableReflection, []);
        $this->assertInstanceOf(LoggerInterface::class, \current($resolved));
        $this->assertInstanceOf(NullLogger::class, \current($resolved));
    }

    public function testAppendResolver(): void
    {
        $resolver = new ArgumentResolver();
        $resolver->appendResolver(new Fixtures\BlankValueResolver());

        $callableReflection = $this->createCallableReflection(
            function (?ArgumentValueResolverInterface $argument = null): ?ArgumentValueResolverInterface {
                return $argument;
            }
        );

        $resolved = $resolver->getParameters($callableReflection);
        $this->assertNull(\current($resolved));
    }

    public function testPrependResolver(): void
    {
        $resolver = new ArgumentResolver();
        $resolver->prependResolver(new Fixtures\BlankValueResolver());

        $callableReflection = $this->createCallableReflection(
            function (?ArgumentValueResolverInterface $argument = null): ?ArgumentValueResolverInterface {
                return $argument;
            }
        );

        $resolved = $resolver->getParameters($callableReflection);
        $this->assertInstanceOf(ArgumentValueResolverInterface::class, \current($resolved));
        $this->assertInstanceOf(Fixtures\BlankValueResolver::class, \current($resolved));
    }

    /**
     * @return Generator
     */
    public function implicitCallableContainerData(): Generator
    {
        yield 'Container callable with named variable' => [
            [new Fixtures\BlankClass(), 'methodWithNamedParameter'],
        ];

        yield 'Container callable with named typehint variable' => [
            [new Fixtures\BlankClass(), 'methodWithTypeHintParameter'],
        ];
    }

    /**
     * @return Generator
     */
    public function implicitCallableData(): Generator
    {
        $logger = new NullLogger();

        yield 'Callable without variable' => [
            [new Fixtures\BlankClass(), 'method'],
            [],
            [],
        ];

        yield 'String Callable without variable' => [
            'phpinfo',
            [],
            [],
        ];

        yield 'Callable with named variable' => [
            [new Fixtures\BlankClass(), 'methodWithNamedParameter'],
            ['logger' => $logger],
            [$logger],
        ];

        yield 'Callable with named indexed variable' => [
            [new Fixtures\BlankClass(), 'methodWithNamedParameter'],
            [$logger],
            [$logger],
        ];

        yield 'Callable with named typehint variable' => [
            [new Fixtures\BlankClass(), 'methodWithTypeHintParameter'],
            ['logger' => $logger],
            [$logger],
        ];

        yield 'Callable with exact named typehint variable' => [
            [new Fixtures\BlankClass(), 'methodWithTypeHintParameter'],
            [LoggerInterface::class => $logger],
            [$logger],
        ];

        yield 'Callable with named indexed typehint variable' => [
            [new Fixtures\BlankClass(), 'methodWithTypeHintParameter'],
            [$logger],
            [$logger],
        ];

        yield 'Closure callable with named indexed variable' => [
            function ($name): string {
                return $name;
            },
            ['Divine'],
            ['Divine'],
        ];

        yield 'Closure Callable with typehint variables' => [
            function (string $name, int $num): string {
                return $name . $num;
            },
            [1 => 23, 0 => 'Divine'],
            ['Divine', 23],
        ];

        yield 'Closure Callable with default typehint variables' => [
            function (string $name, int $num = 23): string {
                return $name . $num;
            },
            ['name' => 'Divine'],
            ['Divine', 23],
        ];

        yield 'Closure Callable with default undefined variable' => [
            function ($name, $bar = null): string {
                return $name . $num;
            },
            ['name' => 'Divine'],
            ['Divine', null],
        ];

        yield 'Closure Callable with default highest priority variable' => [
            function ($foo, $bar = 300) {
                return [$foo, $bar];
            },
            ['foo' => 'foo', 'bar'],
            ['bar', 300],
        ];
    }

    private function createCallableReflection(callable $callable): ReflectionFunctionAbstract
    {
        return CallableReflection::create($callable);
    }
}
