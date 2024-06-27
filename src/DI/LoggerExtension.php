<?php

declare(strict_types = 1);

namespace Apploud\Logger\DI;

use Apploud\Logger\Logger;
use Contributte\Monolog\DI\MonologExtension;
use Monolog\Level;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nette\PhpGenerator\ClassType as ClassTypeAlias;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * @property-read stdClass $config
 */
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
		parent::setConfig($config);
		$this->monolog->setConfig($this->getMonologConfig());
		return $this;
	}


	public function getConfigSchema(): Schema
	{
		$monologSchema = $this->monolog->getConfigSchema();
		if ($monologSchema instanceof Structure) {
			$monologSchema->required(false);
		}

		return Expect::structure([
			'bluescreen' => Expect::structure([
				'logDir' => Expect::string()->required(),
				'logDirUrl' => Expect::string()->required(),
				'minLevel' => Expect::type(Level::class)->default(Level::Warning),
			]),
			'extraHandlers' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
			),
			'extraProcessors' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
			),
			'monolog' => $monologSchema,
			'contributte' => Expect::structure([
				'holder' => Expect::bool(false),
				'manager' => Expect::bool(false),
			]),
		]);
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


	public function afterCompile(ClassTypeAlias $class): void
	{
		$this->monolog->afterCompile($class);
	}


	/**
	 * @return array<mixed>|object
	 */
	private function getMonologConfig(): array|object
	{
		if ($this->config->monolog) {
			return $this->config->monolog;
		}

		$monologConfig = Helpers::expand(
			$this->loadFromFile(__DIR__ . '/monolog.neon'),
			[
				'logDir' => $this->config->bluescreen->logDir,
				'logDirUrl' => $this->config->bluescreen->logDirUrl,
				'minLevel' => $this->config->bluescreen->minLevel,
				'holderEnabled' => $this->config->contributte->holder,
				'managerEnabled' => $this->config->contributte->manager,
			],
			true
		);

		$monologConfig['channel']['default']['handlers'] = array_merge($monologConfig['channel']['default']['handlers'], $this->config->extraHandlers);
		$monologConfig['channel']['default']['processors'] = array_merge($monologConfig['channel']['default']['processors'], $this->config->extraProcessors);

		$processor = new Processor();
		return $processor->process($this->monolog->getConfigSchema(), $monologConfig);
	}
}
