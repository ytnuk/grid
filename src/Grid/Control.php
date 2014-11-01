<?php

namespace WebEdit\Grid;

use Nette\Forms;
use WebEdit\Application;

/**
 * Class Control
 *
 * @package WebEdit\Grid
 */
final class Control extends Application\Control
{

	/**
	 * @var array
	 * @persistent
	 */
	public $order = [];

	/**
	 * @var array
	 * @persistent
	 */
	public $filter = [];

	/**
	 * @var callable
	 */
	private $form;

	/**
	 * @var callable
	 */
	private $items;

	/**
	 * @var string
	 */
	private $active;

	/**
	 * @var callable
	 */
	private $link;

	/**
	 * @var array
	 */
	private $filteredInputs = [];

	/**
	 * @var array
	 */
	private $limitInputs;

	/**
	 * @param callable $form
	 * @param callable $items
	 */
	public function __construct(callable $form, callable $items)
	{
		$this->form = $form;
		$this->items = $items;
	}

	/**
	 * @param array $filteredInputs
	 *
	 * @return $this
	 */
	public function filterInputs(array $filteredInputs)
	{
		$this->filteredInputs = $filteredInputs;

		return $this;
	}

	/**
	 * @param Forms\Controls\SubmitButton $button
	 */
	public function filter(Forms\Controls\SubmitButton $button)
	{
		$this->filter = $this->prepareFilterValues($button->getForm()
			->getValues(TRUE));
		$this->redirect('this');
	}

	/**
	 * @param array $values
	 *
	 * @return array
	 */
	private function prepareFilterValues(array $values)
	{
		$data = [];
		foreach ($values as $key => $value) {
			if (is_array($value)) {
				$value = $this->prepareFilterValues($value);
			}
			if ($value) {
				$data[$key] = $value;
			}
		}

		return $data;
	}

	/**
	 * @param string $htmlName
	 */
	public function handleOrder($htmlName)
	{
		$this->order = $this->prepareOrderValues($this->htmlNameToArray($htmlName), $this->order);
		$this->redirect('this');
	}

	/**
	 * @param array $keys
	 * @param array $values
	 *
	 * @return array
	 */
	private function prepareOrderValues(array $keys, array $values)
	{
		$key = array_shift($keys);
		end($values);
		$active = $key === key($values);
		$value = isset($values[$key]) ? $values[$key] : [];
		unset($values[$key]);
		$values[$key] = count($keys) ? $this->prepareOrderValues($keys, $value) : (! $active ? 'ASC' : ($value === 'ASC' ? 'DESC' : NULL));

		return $values;
	}

	/**
	 * @param string $htmlName
	 *
	 * @return array
	 */
	private function htmlNameToArray($htmlName)
	{
		return explode('[', str_replace([']'], NULL, $htmlName));
	}

	/**
	 * @param callable $link
	 *
	 * @return $this
	 */
	public function setLink(callable $link)
	{
		$this->link = $link;

		return $this;
	}

	/**
	 * @param array $limit
	 *
	 * @return $this
	 */
	public function limitInputs(array $limit)
	{
		$this->limitInputs = $limit;

		return $this;
	}

	/**
	 * @param $control
	 */
	protected function attached($control)
	{
		parent::attached($control);
		$this->active = $this->getParameter('active');
		$this->items = call_user_func($this->items, $this->order, $this->filter);
		if ( ! is_array($this->items)) {
			$this->items = iterator_to_array($this->items);
		}
		if ( ! $header = array_search(NULL, $this->items)) {
			$this->items = array_reverse($this->items, TRUE);
			$this->items[] = NULL;
			$this->items = array_reverse($this->items, TRUE);
			$keys = array_keys($this->items);
			$header = reset($keys);
		}
		$this['form'][$header]->setDefaults($this->filter)
			->addSubmit('filter', 'grid.filter')
			->setValidationScope(FALSE)->onClick[] = [
			$this,
			'filter'
		];
		foreach ($this->items as $key => $item) {
			$controls = [];
			$form = $this['form'][$key];
			foreach ($form->getControls() as $control) {
				$controls[$control->getHtmlName()] = $control;
			}
			$inputsCount = 0;
			$this->items[$key] = (object) [
				'id' => $key,
				'item' => $item,
				'form' => $form,
				'inputs' => array_filter($controls, function ($control) use ($form, $item, &$inputsCount) {
					if ($this->limitInputs && $inputsCount > $this->limitInputs) {
						return FALSE;
					}
					$inputsCount++;
					if ($item === NULL) {
						$control->setAttribute('onchange', 'this.form.filter.click()');
					}
					$filtered = ! (bool) $this->filteredInputs;
					foreach ($this->filteredInputs as $name) {
						if (strpos($control->getHtmlName(), $name) === 0) {
							$filtered = TRUE;
							break;
						}
					}

					return $filtered && ! $control instanceof Forms\Controls\HiddenField;
				}),
				'hidden' => array_filter($controls, function ($control) {
					return $control instanceof Forms\Controls\HiddenField;
				}),
				'link' => is_callable($this->link) ? call_user_func($this->link, $item) : $this->link,
				'active' => $key !== $header ? $this->active === (string) $key : TRUE,
			];
		}
	}

	protected function startup()
	{
		$this->template->items = $this->items;
		$this->template->filter = $this->filter;
		$this->template->orderBy = $this->arrayToHtmlName($this->order, $sort);
		$this->template->order = $sort;
		$this->template->filteredInputs = $this->filteredInputs;
	}

	/**
	 * @param array $values
	 * @param array $value
	 * @param bool $wrap
	 *
	 * @return string
	 */
	private function arrayToHtmlName(array $values, array &$value = NULL, $wrap = FALSE)
	{
		$value = end($values);
		$key = key($values);
		if ($wrap) {
			$key = '[' . $key . ']';
		}

		return $key . (is_array($value) ? $this->arrayToHtmlName($value, $value, TRUE) : NULL);
	}

	/**
	 * @return Application\Control\Multiplier
	 */
	protected function createComponentForm()
	{
		return new Application\Control\Multiplier(function ($key) {
			return call_user_func($this->form, $this->items[$key]);
		});
	}
}