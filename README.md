# Apploud Logger

This package integrates [Monolog](https://github.com/Seldaek/monolog) into Apploud projects.

It uses [contributte ingegration](https://github.com/contributte/monolog) and adds some features:

- `\Apploud\Logger\Logger` class with `logException()` method to easily log exceptions
- default logger configuration, which can be updated across projects with `composer update`
- local storage driver, which adds bluescreen URL to logs accessible through webserver

## Installation

Just use composer:

```shell
composer require apploud/logger
```

### Minimal configuration

Add extension to `config.neon`:

```yaml
extensions:
	logger: Apploud\Logger\DI\LoggerExtension

logger:
	bluescreen:
		logDir: %appDir%/../log # path to store bluescreens in
		logDirUrl: %server.baseUrl%/_belogs # URL to bluescreens directory, without trailing slash
```

## Usage

Extension adds `\Apploud\Logger\Logger` to DI container. This logger also implements `\Psr\Log\LoggerInterface` interface.

### Advanced configuration

See commented example:

```yaml
logger:
	bluescreen:
		logDir: %appDir%/../log # path to store bluescreens in
		logDirUrl: %server.baseUrl%/_belogs # URL to bluescreens directory, without trailing slash
		minLevel: Monolog\Level::Warning # minimal Level for creating bluescreens, defaults to Warning
	jwt:
		process: true # if true, JWT processor adds decoded fields from JTW token in Authorization header to logs
		fields: # which fields from JWT should be added to logs
			- sub
			- jti
		extraFieldName: jwt # field name in `extra` section
	extraHandlers: # Adds handlers to default logger, syntax same as in contributte extension
		-
			factory: Monolog\Handler\StreamHandler("%appDir%/../log/default.log")
			setup:
				- setFormatter(Monolog\Formatter\JsonFormatter())
	extraProcessors: # Adds processors to default logger, syntax same as in contributte extension
		- Monolog\Processor\MemoryPeakUsageProcessor()
	channels: # Adds other loggers, syntax same as in contributte extension
		someChannel:
			...
		someOtherChannel:
			...
	contributte:
		manager: false # if true, enables logger manager (see contributte docs)
		holder: false # if true, enables logger holder (see contributte docs)
	monolog: # can be used to configure contributte extension directly, if used, previous options are ignored
```
