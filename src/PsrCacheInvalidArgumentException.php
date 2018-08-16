<?php

namespace TomasKarlik\PsrNetteCache;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;


class PsrCacheInvalidArgumentException extends InvalidArgumentException implements PsrInvalidArgumentException
{

}
