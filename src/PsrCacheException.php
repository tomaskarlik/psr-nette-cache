<?php

namespace TomasKarlik\PsrNetteCache;

use Exception;
use Psr\SimpleCache\CacheException;


class PsrCacheException extends Exception implements CacheException
{

}
