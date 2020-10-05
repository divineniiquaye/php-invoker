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

namespace DivineNii\Invoker\ArgumentResolver;

use DivineNii\Invoker\Interfaces\ArgumentValueResolverInterface;
use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Tries to map an associative array (string-indexed) to the parameter names.
 * E.g. `->call($callable, ['foo' => 'bar'])` will inject the string `'bar'`
 * in the parameter named `$foo`.
 * Parameters that are not indexed by a string are ignored.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class NamedValueResolver implements ArgumentValueResolverInterface
{
    /** @var ContainerInterface|null */
    private $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ReflectionParameter $parameter, array $providedParameters)
    {
        $name  = $parameter->name;

        // Inject entries from a DI container using the parameter names.
        if ($name && (null !== $this->container && $this->container->has($name))) {
            return $this->container->get($name);
        }

        if (\array_key_exists($name, $providedParameters)) {
            return $providedParameters[$name];
        }
    }
}
