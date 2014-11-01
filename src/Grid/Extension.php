<?php

namespace WebEdit\Grid;

use Kdyby\Translation;
use Nette\DI;
use WebEdit\Config;

/**
 * Class Extension
 *
 * @package WebEdit\Form
 */
final class Extension extends DI\CompilerExtension implements Config\Provider
{

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			Translation\DI\TranslationExtension::class => [
				'dirs' => [
					__DIR__ . '/../../locale'
				]
			]
		];
	}
}
