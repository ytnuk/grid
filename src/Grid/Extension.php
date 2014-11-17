<?php

namespace WebEdit\Grid;

use Kdyby;
use Nette;
use WebEdit;

/**
 * Class Extension
 *
 * @package WebEdit\Form
 */
final class Extension extends Nette\DI\CompilerExtension implements WebEdit\Config\Provider
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
