<?php
namespace Asenine\Interfaces;

abstract class Iterator implements \Iterator
{
	protected $items = array();
	private $pos = 0;

	public function add($item)
	{
		$this->items[] = $item;
	}

	public function current() {
		return $this->items[$this->pos];
	}

	public function key() {
		return $this->pos;
	}

	public function next() {
		++$this->pos;
	}

	public function rewind() {
		$this->pos = 0;
	}

	public function valid() {
		return isset($this->items[$this->pos]);
	}
}