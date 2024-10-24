<?php

declare(strict_types = 1);

namespace Apploud\Logger;

use Apploud\Logger\Processor\UuidProcessor;
use Apploud\Logger\Record\LogRecordUuidProvider;
use Monolog\Logger as MonologLogger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

class Logger extends AbstractLogger implements LogRecordUuidProvider
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


	public function getLastRecordUuid(): ?string
	{
		if ($this->logger instanceof MonologLogger) {
			foreach ($this->logger->getProcessors() as $processor) {
				if ($processor instanceof UuidProcessor) {
					return $processor->getLastRecordUuid();
				}
			}
		}

		return null;
	}
}
