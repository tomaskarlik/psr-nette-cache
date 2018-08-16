<?php

declare(strict_types = 1);

namespace TomasKarlik\PsrNetteCache;

use Nette\DI\CompilerExtension;


final class Extension extends CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('PsrCache'))
			->setClass(PsrCache::class);
	}

}
