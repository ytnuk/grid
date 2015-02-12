<?php

namespace Ytnuk\Grid;

use Nette;
use Ytnuk;

/**
 * Class Control
 *
 * @package Ytnuk\Grid
 */
final class Control extends Ytnuk\Application\Control
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
	 * @param Nette\Forms\Controls\SubmitButton $button
	 */
	public function filter(Nette\Forms\Controls\SubmitButton $button)
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

	public function handleEdit($id)
	{
		if ($this->getPresenter()
			->isAjax()
		) {
			$this->active = $id;
			$this->redrawControl();
			//TODO: every row should be separate component with @persistent $editable and here just set component with id to editable=TRUE & redraw only that component
		} else {
			$this->redirect('this', ['active' => $id]);
		}
	}

	/**
	 * @param $control
	 */
	protected function attached($control)
	{
		parent::attached($control);
		$this->active = $this->getParameter('active');
	}

	protected function startup() //TODO: ultra massive refactor
	{
		if ( ! $header = array_search(NULL, $this->getItems())) {
			$this->items = array_reverse($this->getItems(), TRUE);
			$this->items[] = NULL;
			$this->items = array_reverse($this->getItems(), TRUE);
			$keys = array_keys($this->items);
			$header = reset($keys);
		}
		$this['form'][$header]->setDefaults($this->filter)
			->addSubmit('filter', 'grid.filter')
			->setValidationScope(FALSE)->onClick[] = [
			$this,
			'filter'
		];
		foreach ($this->getItems() as $key => $item) {
			$controls = [];
			$form = $this['form'][$key];
			foreach ($form->getControls() as $control) {
				$controls[$control->getHtmlName()] = $control;
			}
			$inputsCount = 0;
			$this->setItem($key, (object) [
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

					return $filtered && ! $control instanceof Nette\Forms\Controls\HiddenField;
				}),
				'hidden' => array_filter($controls, function ($control) {
					return $control instanceof Nette\Forms\Controls\HiddenField;
				}),
				'link' => is_callable($this->link) ? call_user_func($this->link, $item) : $this->link,
				'active' => $key !== $header ? $this->active === (string) $key : TRUE,
			]);
		}
		$this->getTemplate()
			->add('items', $this->getItems())
			->add('filter', $this->filter)
			->add('orderBy', $this->arrayToHtmlName($this->order, $sort))
			->add('order', $sort)
			->add('filteredInputs', $this->filteredInputs);
	}

	/**
	 * @return array
	 */
	public function getItems()
	{
		if ( ! is_array($this->items)) {
			$this->items = call_user_func($this->items, $this->order, $this->filter);
			if ($this->items instanceof \Traversable) {
				$this->items = iterator_to_array($this->items);
			}
		}

		return $this->items;
	}

	public function setItems(array $items)
	{
		$this->items = $items;
	}

	public function setItem($key, $item)
	{
		$items = $this->getItems();
		$items[$key] = $item;
		$this->setItems($items);
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
	 * @return Nette\Application\UI\Multiplier
	 */
	protected function createComponentForm()
	{
		return new Nette\Application\UI\Multiplier(function ($key) {
			return call_user_func($this->form, $this->getItem($key));
		});
	}

	public function getItem($key)
	{
		$items = $this->getItems();

		return $items[$key];
	}
}
