<?php

declare(strict_types = 1);

namespace Apploud\Logger\Tracy;

use Mangoweb\MonologTracyHandler\RemoteStorageDriver;

class LocalStorageDriver implements RemoteStorageDriver
{
	private string $logDirUrl;


	public function __construct(string $logDirUrl)
	{
		$this->logDirUrl = $logDirUrl;
	}


	public function getUrl(string $localName): ?string
	{
		return sprintf('%s/%s', $this->logDirUrl, $localName);
	}


	/**
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	public function upload(string $localPath): bool
	{
		return false;
	}
}
