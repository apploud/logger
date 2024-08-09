<?php

declare(strict_types = 1);

namespace Apploud\Logger\DI;

use Apploud\Logger\Logger;
use Apploud\Logger\Processor\JwtProcessor;
use Apploud\Logger\Processor\RequestIdProcessor;
use Contributte\Monolog\DI\MonologExtension;
use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
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
			'requestIds' => Expect::structure([
				'process' => Expect::bool(true),
				'requestIdHeader' => Expect::string('X-Request-ID'),
				'correlationIdHeader' => Expect::string('X-Correlation-ID'),
				'extraFieldName' => Expect::string('request'),
			]),
			'jwt' => Expect::structure([
				'process' => Expect::bool(true),
				'fields' => Expect::arrayOf('string')->default(null),
				'extraFieldName' => Expect::string('jwt'),
			]),
			'extraHandlers' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
			),
			'extraProcessors' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
			),
			'channels' => Expect::arrayOf(Expect::structure([
				'handlers' => Expect::arrayOf(
					Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
				)->required()->min(1),
				'processors' => Expect::arrayOf(
					Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
				),
			])),
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

		$builder = $this->getContainerBuilder();

		$additionalChannels = [];
		if ($this->config->channels) {
			if (array_key_exists('default', $this->config->channels)) {
				$message = '%s.channels cannot contain channel with name `default`.'
					. ' Use `extraHandlers` and `extraProcessors` to modify default channel or use `monolog` to replace it.';
				throw new InvalidStateException(sprintf($message, $this->name));
			}

			$additionalChannels = $this->config->channels;
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

		if ($this->config->requestIds->process) {
			$monologConfig['channel']['default']['processors'][] = new Statement(RequestIdProcessor::class, [
				'requestIdHeader' => $this->config->requestIds->requestIdHeader,
				'correlationIdHeader' => $this->config->requestIds->correlationIdHeader,
				'extraFieldName' => $this->config->requestIds->extraFieldName,
			]);
		}

		if ($this->config->jwt->process) {
			$builder->addDefinition($this->prefix('jwt.decoder'))->setFactory(JoseEncoder::class);
			$builder->addDefinition($this->prefix('jwt.parser'))->setFactory(Parser::class);

			$monologConfig['channel']['default']['processors'][] = new Statement(JwtProcessor::class, [
				'fields' => $this->config->jwt->fields,
				'extraFieldName' => $this->config->jwt->extraFieldName,
			]);
		}

		$monologConfig['channel'] = array_merge($monologConfig['channel'], $additionalChannels);
		$monologConfig['channel']['default']['handlers'] = array_merge($monologConfig['channel']['default']['handlers'], $this->config->extraHandlers);
		$monologConfig['channel']['default']['processors'] = array_merge($monologConfig['channel']['default']['processors'], $this->config->extraProcessors);

		$processor = new Processor();
		return $processor->process($this->monolog->getConfigSchema(), $monologConfig);
	}
}
