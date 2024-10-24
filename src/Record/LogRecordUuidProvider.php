<?php

declare(strict_types = 1);

namespace Apploud\Logger\Record;

interface LogRecordUuidProvider
{
	public function getLastRecordUuid(): ?string;
}
