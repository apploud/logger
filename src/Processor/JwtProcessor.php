<?php

declare(strict_types = 1);

namespace Apploud\Logger\Processor;

use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Psr\Http\Message\ServerRequestInterface;

class JwtProcessor implements ProcessorInterface
{
	private string $extraFieldName;

	/** @var array<non-empty-string, non-empty-string> */
	private array $fields = [
		'sub' => 'sub',
		'jti' => 'jti',
	];

	private Parser $parser;

	private ?string $jwt;


	/**
	 * @param array<non-empty-string,non-empty-string>|list<non-empty-string>|null $fields
	 */
	public function __construct(Parser $parser, ServerRequestInterface $request, ?array $fields = null, string $extraFieldName = 'jwt')
	{
		$this->extraFieldName = $extraFieldName;
		$this->parser = $parser;

		$authHeader = $request->getHeader('Authorization');

		if (count($authHeader) === 1) {
			preg_match('/^\s*Bearer\s+(\S+)$/i', $authHeader[0], $matches);
			$this->jwt = $matches[1] ?? null;
		}

		if ($fields === null) {
			return;
		}

		if (array_is_list($fields)) {
			$this->fields = array_combine($fields, $fields);
			return;
		}

		$this->fields = $fields; // https://github.com/phpstan/phpstan/issues/11382 @phpstan-ignore assign.propertyType
	}


	/**
	 * @return array<string, string>
	 */
	private function getFields(): array
	{
		if ($this->jwt === null || $this->jwt === '') {
			return [];
		}

		/** @var UnencryptedToken $token */
		$token = $this->parser->parse($this->jwt);
		$jwtPayload = $token->claims();

		$fields = [];

		foreach ($this->fields as $extraName => $payloadName) {
			$fields[$extraName] = $jwtPayload->get($payloadName);
		}

		return array_filter($fields);
	}


	public function __invoke(LogRecord $record): LogRecord
	{
		$fields = $this->getFields();

		if ($fields !== []) {
			$record->extra[$this->extraFieldName] = $fields;
		}

		return $record;
	}
}
