<?php

declare(strict_types = 1);

namespace Apploud\Logger\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestIdProcessor implements ProcessorInterface
{
	private ServerRequestInterface $request;

	private string $requestIdHeader;

	private string $correlationIdHeader;

	private string $extraFieldName;


	public function __construct(
		ServerRequestInterface $request,
		string $requestIdHeader = 'X-Request-ID',
		string $correlationIdHeader = 'X-Correlation-ID',
		string $extraFieldName = 'request'
	) {
		$this->requestIdHeader = $requestIdHeader;
		$this->correlationIdHeader = $correlationIdHeader;
		$this->request = $request;
		$this->extraFieldName = $extraFieldName;
	}


	public function __invoke(LogRecord $record): LogRecord
	{
		$ids = array_filter([
			'req_id' => $this->request->getHeader($this->requestIdHeader)[0] ?? null,
			'correlation_id' => $this->request->getHeader($this->correlationIdHeader)[0] ?? null,
		]);

		if ($ids !== []) {
			$record->extra[$this->extraFieldName] = $ids;
		}

		return $record;
	}
}
