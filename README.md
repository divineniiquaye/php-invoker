# The PHP Invoker

[![Latest Version](https://img.shields.io/packagist/v/divineniiquaye/php-invoker.svg?style=flat-square)](https://packagist.org/packages/divineniiquaye/php-invoker)
[![Software License](https://img.shields.io/badge/License-BSD--3-brightgreen.svg?style=flat-square)](LICENSE)
[![Workflow Status](https://img.shields.io/github/workflow/status/divineniiquaye/php-invoker/Tests?style=flat-square)](https://github.com/divineniiquaye/php-invoker/actions?query=workflow%3ATests)
[![Code Maintainability](https://img.shields.io/codeclimate/maintainability/divineniiquaye/php-invoker?style=flat-square)](https://codeclimate.com/github/divineniiquaye/php-invoker)
[![Coverage Status](https://img.shields.io/codecov/c/github/divineniiquaye/php-invoker?style=flat-square)](https://codecov.io/gh/divineniiquaye/php-invoker)
[![Quality Score](https://img.shields.io/scrutinizer/g/divineniiquaye/php-invoker.svg?style=flat-square)](https://scrutinizer-ci.com/g/divineniiquaye/php-invoker)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://biurad.com/sponsor)

**divineniiquaye/php-invoker** is a php library that allows invoking callables with named parameters in a generic and extensible way for [PHP] 7.1+, based on reference implementation [PHP-DI Invoker][di-invoker] created by [Matthieu Napoli][@mnapoli]. This library provides clear extension points to let frameworks/projects implement any kind of dependency injection support they want, but not limited to dependency injection. Again, any [PSR-11] compliant container can be provided.

## 📦 Installation & Basic Usage

This project requires [PHP] 7.1 or higher. The recommended way to install, is via [Composer]. Simply run:

```bash
$ composer require divineniiquaye/php-invoker
```

Let's you working on a project and you need to invoke some named parameters in callables with whatever the order of parameters, but should be matched by their names or instance. Then we'll need an over-engineered `call_user_func()`.

In short, this library is meant to be a base building block for calling a function with named parameters and/or dependency injection.

Using `DivineNii\Invoker\Invoker` class method `call`:

```php
<?php
$invoker = new DivineNii\Invoker\Invoker;

$invoker->call(function () {
    echo 'Hello world!';
});

// Simple parameter array
$invoker->call(function ($name) {
    echo 'Hello ' . $name;
}, ['John']);

// Named parameters
$invoker->call(function ($name) {
    echo 'Hello ' . $name;
}, [
    'name' => 'John'
]);

// Typehint parameters
$invoker->call(function (string $name) {
    echo 'Hello ' . $name;
}, [
    'name' => 'John'
]);

// Use the default value
$invoker->call(function ($name = 'world') {
    echo 'Hello ' . $name;
});

// Invoke any PHP callable
$invoker->call(['MyClass', 'myStaticMethod']);

// Using Class::method syntax
$invoker->call('MyClass::myStaticMethod');

// Using ":" pattern syntax
$invoker->call('MyClass:myMethod');

// Using "@" pattern syntax
$invoker->call('MyClass@myMethod');
```

Using `DivineNii\Invoker\ParameterResolver` class in `DivineNii\Invoker\Invoker` class:

Extending the behavior of the `DivineNii\Invoker\Invoker` is easy and is done by adding a callable to [`ParameterResolver`](https://github.com/divineniiquaye/php-invoker/blob/master/src/ParameterResolver.php) class:

```php
<?php
use ReflectionFunctionAbstract;

class MyParameterResolver
{
    /**
     * @param ReflectionFunctionAbstract $reflection
     * @param array<int|string,mixed>    $providedParameters
     * @param array<int|string,mixed>    $resolvedParameters
     *
     * @return array<int|mixed>
     */
    public function resolve(ReflectionFunctionAbstract $reflection, array $providedParameters, array $resolvedParameters): array
    {
        //....
    }
}
```

- `$providedParameters` contains the parameters provided by the user when calling `$invoker->call($callable, $parameters)`
- `$resolvedParameters` contains parameters that have already been resolved by other parameter resolvers

An `DivineNii\Invoker\Invoker` can chain multiple parameter resolvers to mix behaviors, e.g. you can mix "named parameters" support with "dependency injection" support. This is why a `DivineNii\Invoker\ParameterResolver` should skip parameters that are already resolved in`callables $resolvedParameters argument.

Here is an implementation example for dumb dependency injection that creates a new instance of the classes type-hinted:

```php
class MyParameterResolver
{
    /**
     * @param ReflectionFunctionAbstract $reflection
     * @param array<int|string,mixed>    $providedParameters
     * @param array<int|string,mixed>    $resolvedParameters
     *
     * @return array<int|mixed>
     */
    public function static resolve(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $parameters = $reflection->getParameters();
 
        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $parameters = \array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $class = $parameter->getClass();
            if ($class) {
                $resolvedParameters[$index] = $class->newInstance();
            }
        }

        return $resolvedParameters;
    }
}
```

To use it:

```php
<?php
$invoker = new DivineNii\Invoker\Invoker([MyParameterResolver::class, 'resolve']);

$invoker->call(function (ArticleManager $articleManager) {
    $articleManager->publishArticle('Hello world', 'This is the article content.');
});
```

A new instance of `ArticleManager` will be created by our parameter resolver. The fun starts to happen when we want to add support for many things:

- named parameters
- dependency injection for type-hinted parameters
- ...

It allows to support even the weirdest use cases like:

```php
$parameters = [];

// First parameter will receive "Welcome"
$parameters[] = 'Welcome';

// Parameter named "content" will receive "Hello world!"
$parameters['content'] = 'Hello world!';

// $published is not defined so it will use its default value
$invoker->call(function ($title, $content, $published = true) {
    // ...
}, $parameters);
```

Rather than have you re-implement support for dependency injection with different containers every time, this package ships with 2 optional resolvers:

- This resolver will inject container entries by searching for the class name using the type-hint:

    ```php
    $invoker->call(function (Psr\Logger\LoggerInterface $logger) {
        // ...
    });
    ```

    In this example it will `->get('Psr\Logger\LoggerInterface')` from the container and inject it, but if instance of interface exist in `$providedParameters`, it also get injected.

- This resolver will inject container entries by searching for the name of the parameter:

    ```php
    $invoker->call(function ($twig) {
        // ...
    });
    ```

    In this example it will `->get('twig')` from the container and inject it or from `$providedParameters`.

The `DivineNii\Invoker\Invoker` can be wired to your DI container to resolve the callables, but can resolve all callables including invokable class or object.

For example with an invokable class:

```php
class MyHandler
{
    public function __invoke()
    {
        // ...
    }
}

// By default this work
$invoker->call('MyHandler');

// If we set up the container to use
$invoker = new Invoker\Invoker([], $container);
// Now 'MyHandler' parameters is resolved using the container if any!
$invoker->call('MyHandler');
```

The same works for a class method:

```php
class WelcomeController
{
    public function home()
    {
        // ...
    }
}

// By default this doesn't work: home() is not a static method
$invoker->call(['WelcomeController', 'home']);

// If we set up the container to use
$invoker = new Invoker\Invoker([], $container);
// Now 'WelcomeController' is resolved using the container!
$invoker->call(['WelcomeController', 'home']);
// Alternatively we can use the Class::method syntax
$invoker->call('WelcomeController::home');
```

## 📓 Documentation

For in-depth documentation before using this library. Full documentation on advanced usage, configuration, and customization can be found at [docs.biurad.com][docs].

## ⏫ Upgrading

Information on how to upgrade to newer versions of this library can be found in the [UPGRADE].

## 🏷️ Changelog

[SemVer](http://semver.org/) is followed closely. Minor and patch releases should not introduce breaking changes to the codebase; See [CHANGELOG] for more information on what has changed recently.

Any classes or methods marked `@internal` are not intended for use outside of this library and are subject to breaking changes at any time, so please avoid using them.

## 🛠️ Maintenance & Support

When a new **major** version is released (`1.0`, `2.0`, etc), the previous one (`0.19.x`) will receive bug fixes for _at least_ 3 months and security updates for 6 months after that new release comes out.

(This policy may change in the future and exceptions may be made on a case-by-case basis.)

**Professional support, including notification of new releases and security updates, is available at [Biurad Commits][commit].**

## 👷‍♀️ Contributing

To report a security vulnerability, please use the [Biurad Security](https://security.biurad.com). We will coordinate the fix and eventually commit the solution in this project.

Contributions to this library are **welcome**, especially ones that:

- Improve usability or flexibility without compromising our ability to adhere to [PSR-12] coding stardand.
- Optimize performance
- Fix issues with adhering to [PSR-11] support and backward compatability.

Please see [CONTRIBUTING] for additional details.

## 🧪 Testing

```bash
$ composer test
```

This will tests divineniiquaye/php-invoker will run against PHP 7.2 version or higher.

## 👥 Credits & Acknowledgements

- [Divine Niiquaye Ibok][@divineniiquaye]
- [All Contributors][]

## 🙌 Sponsors

Are you interested in sponsoring development of this project? Reach out and support us on [Patreon](https://www.patreon.com/biurad) or see <https://biurad.com/sponsor> for a list of ways to contribute.

## 📄 License

**divineniiquaye/php-invoker** is licensed under the BSD-3 license. See the [`LICENSE`](LICENSE) file for more details.

## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][@divineniiquaye]. Members of the [Biurad Lap][] Leadership Team may occasionally assist with some of these duties.

## 🗺️ Who Uses It?

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us an [email] or [message] mentioning this library. We publish all received request's at <https://patreons.biurad.com>.

Check out the other cool things people are doing with `divineniiquaye/php-invoker`: <https://packagist.org/packages/divineniiquaye/php-invoker/dependents>

[Composer]: https://getcomposer.org
[@divineniiquaye]: https://github.com/divineniiquaye
[docs]: https://docs.biurad.com/php-invoker
[commit]: https://commits.biurad.com/php-invoker.git
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[All Contributors]: https://github.com/divineniiquaye/php-invoker/contributors
[Biurad Lap]: https://team.biurad.com
[email]: support@biurad.com
[message]: https://projects.biurad.com/message
[PHP]: https://php.net
[PSR-11]: http://www.php-fig.org/psr/psr-11/
[PSR-12]: http://www.php-fig.org/psr/psr-12/
[@mnapoli]: https://github.com/mnapoli
[di-invoker]: https://github.com/PHP-DI/Invoker
