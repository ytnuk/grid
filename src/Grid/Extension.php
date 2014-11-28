<?php

namespace Kutny\Grid;

use Kdyby;
use Nette;
use Kutny;

/**
 * Class Extension
 *
 * @package Kutny\Form
 */
final class Extension extends Nette\DI\CompilerExtension implements Kutny\Config\Provider
{

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			Kdyby\Translation\DI\TranslationExtension::class => [
				'dirs' => [
					__DIR__ . '/../../locale'
				]
			]
		];
	}
}
