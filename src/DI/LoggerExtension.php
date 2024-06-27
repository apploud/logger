<?php

declare(strict_types = 1);

namespace Apploud\Logger\DI;

use Apploud\Logger\Logger;
use Contributte\Monolog\DI\MonologExtension;
use Psr\Log\LoggerInterface;

class LoggerExtension extends MonologExtension
{
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$originalLogger = $builder->getDefinitionByType(LoggerInterface::class);
		$originalLogger->setAutowired(false);
		$builder->addDefinition($this->prefix('logger'))
			->setFactory(Logger::class, [$originalLogger]);
	}
}
