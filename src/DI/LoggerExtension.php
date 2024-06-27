<?php

declare(strict_types = 1);

namespace Apploud\Logger\DI;

use Apploud\Logger\Logger;
use Contributte\Monolog\DI\MonologExtension;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Psr\Log\LoggerInterface;

class LoggerExtension extends CompilerExtension
{
	private MonologExtension $monolog;


	public function __construct()
	{
		$this->monolog = new MonologExtension();
	}


	public function setCompiler(Compiler $compiler, string $name): static
	{
		parent::setCompiler($compiler, $name);
		$this->monolog->setCompiler($compiler, $this->prefix('monolog'));
		return $this;
	}


	/**
	 * @param array<mixed>|object $config
	 */
	public function setConfig(array|object $config): static
	{
		$this->config = $config;
		$this->monolog->setConfig($config->monolog); // @phpstan-ignore property.nonObject
		return $this;
	}


	public function getConfigSchema(): Schema
	{
		return Expect::structure(['monolog' => $this->monolog->getConfigSchema()]);
	}


	public function loadConfiguration(): void
	{
		$this->monolog->loadConfiguration();
	}


	public function beforeCompile(): void
	{
		$this->monolog->beforeCompile();

		$builder = $this->getContainerBuilder();
		$originalLogger = $builder->getDefinitionByType(LoggerInterface::class);
		$originalLogger->setAutowired(false);
		$builder->addDefinition($this->prefix('logger'))
			->setFactory(Logger::class, [$originalLogger]);
	}


	public function afterCompile(ClassType $class): void
	{
		$this->monolog->afterCompile($class);
	}
}
