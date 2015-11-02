<?php
namespace Ytnuk\Grid;

use Kdyby;
use Nette;

final class Extension
	extends Nette\DI\CompilerExtension
	implements Kdyby\Translation\DI\ITranslationProvider
{

	public function getTranslationResources() : array
	{
		return [
			__DIR__ . '/../../locale',
		];
	}
}
