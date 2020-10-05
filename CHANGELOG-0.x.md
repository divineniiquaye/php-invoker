# Change Log

All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [0.9.0] - 2020-10-05
### Added
- Added `DivineNii\Invoker\Interfaces\ArgumentValueResolverInterface` for `DivineNii\Invoker\ArgumentResolver` class

### Changed
- Split `DivineNii\Invoker\ResolverChain` class to under `DivineNii\Invoker\ArgumentResolver` sub-namespace
- Renamed `DivineNii\Invoker\ParameterResolver` class and it's interface to `DivineNii\Invoker\ArgumentResolver`
- Renamed `DivineNii\Invoker\Invoker::getParameterResolver()` method to `DivineNii\Invoker\Invoker::getArgumentResolver()`

## [0.1.2] - 2020-08-15
### Changed
- Update `CHANGELOG-0.x.md` and `CHANGELOG.md` files

### Fixed
- Fixed broken links in README.md file

## [0.1.1] - 2020-08-10
### Added
- Added over 90% tests coverage
- Added `CHANGELOG-0.x.md` file for 0.1.x version updates
- Added `DivineNii\Invoker\ResolverChain` class

### Changed
- Update commit (made major fixtures and changes)
- Use composer v2 on travis test
- Update `README.md` file
- Update `CHANGELOG.md` file
- Updated package description in **composer.json** file
- Update **phpstan.neon.dist** file

## [0.1.0-preview] - 2020-08-07
### Added
- Initial commit

[0.9.0]: https://github.com/divineniiquaye/php-invoker/compare/v0.1.2...v0.9.0
[0.1.2]: https://github.com/divineniiquaye/php-invoker/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/divineniiquaye/php-invoker/compare/v0.1.0...v0.1.1
