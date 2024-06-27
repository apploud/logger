<?php

declare(strict_types = 1);

namespace Apploud\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

class Logger extends AbstractLogger
{
	private LoggerInterface $logger;


	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}


	/**
	 * @param array<mixed> $context
	 * @phpstan-param LogLevel::* $level
	 */
	public function logException(Throwable $exception, ?string $message = null, array $context = [], string $level = LogLevel::ERROR): void
	{
		$context['exception'] = $exception;

		if ($message === null) {
			$message = sprintf('%s: %s', $exception::class, $exception->getMessage());
		}

		$this->log($level, $message, $context);
	}


	/**
	 * @param array<mixed> $context
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
	 */
	public function log($level, Stringable|string $message, array $context = []): void
	{
		$this->logger->log($level, $message, $context);
	}
}
