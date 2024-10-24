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


	public function getLastRecordUuid(): string
	{
		return $this->lastRecordUuid;
	}


	public function __invoke(LogRecord $record): LogRecord
	{
		$this->lastRecordUuid = Uuid::uuid4()->toString();
		$record->extra['id'] = $this->lastRecordUuid;

		return $record;
	}
}
