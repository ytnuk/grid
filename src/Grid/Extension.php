<?php
namespace Ytnuk\Grid;

use Kdyby;
use Nette;
use Ytnuk;

final class Extension
	extends Nette\DI\CompilerExtension
	implements Ytnuk\Config\Provider
{

	public function getConfigResources() : array
	{
		return [
			Kdyby\Translation\DI\TranslationExtension::class => [
				'dirs' => [
					__DIR__ . '/../../locale',
				],
			],
		];
	}
}
