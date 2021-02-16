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

use Psr\Log\LoggerInterface;

class BlankClass
{
    public const BODY = 'sample text';

    public function __invoke(): string
    {
        return self::BODY;
    }

    public function method(): string
    {
        return self::BODY;
    }

    public static function staticMethod(): string
    {
        return self::BODY;
    }

    public function methodWithNamedParameter($logger): LoggerInterface
    {
        return $logger;
    }

    /**
     * @param LoggerInterface    $logger
     * @param ContainerInterface $container
     *
     * @return object[]
     */
    public function methodWithNamedParameters($logger, $container): array
    {
        return [$logger, $container];
    }

    public function methodWithTypeHintParameter(LoggerInterface $logger): LoggerInterface
    {
        return $logger;
    }

    /**
     * @param string          $name
     * @param LoggerInterface $logger
     *
     * @return array<string,LoggerInterface>
     */
    public function methodWithTypeHintParameters(string $name, LoggerInterface $logger): array
    {
        return [$name => $logger];
    }

    public function selfMethod(self $blankClass): self
    {
        return $blankClass;
    }
}
