<?php

declare(strict_types = 1);

namespace Apploud\Logger\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Ramsey\Uuid\Uuid;

class UuidProcessor implements ProcessorInterface
{
	/** @var non-empty-string */
	private string $lastRecordUuid;

	private string $extraFieldName;


	public function __construct(string $extraFieldName = 'applicationErrorId')
	{
		$this->extraFieldName = $extraFieldName;
	}


	public function getLastRecordUuid(): string
	{
		return $this->lastRecordUuid;
	}


	public function __invoke(LogRecord $record): LogRecord
	{
		$this->lastRecordUuid = Uuid::uuid4()->toString();
		$record->extra[$this->extraFieldName] = $this->lastRecordUuid;

		return $record;
	}
}
