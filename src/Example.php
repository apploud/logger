<?php

declare(strict_types = 1);

namespace Apploud\Logger;

use stdClass;

class Example
{
	public function doNothing(stdClass $object): stdClass
	{
		return $object; // this is here just to satisfy requirements checker
	}
}
