<?
namespace Asenine;

abstract class Node
{
	protected static $noclose = array(
		'img',
		'input',
		'meta'
	);

	public $print = true;

	public $attributes = array();
	public $children = array();
	public $tag = 'node';


	public function __toString()
	{
		if (!$this->print) {
			return '';
		}

		$html = '<' . $this->tag . $this->getAttr() . '>';

		if (!in_array($this->tag, self::$noclose)) {
			$html .= $this->getChild() . '</' . $this->tag . '>';
		}

		return $html;
	}


	public function addAttr($key, $value)
	{
		$this->attributes[(string)$key][] = $value;
		return $this;
	}

	public function addChild()
	{
		foreach (func_get_args() as $child) {
			$this->children[] = $child;
		}
		return $this;
	}

	public function addClass()
	{
		foreach (func_get_args() as $class) {
			$this->addAttr('class', $class);
		}
		return $this;
	}

	public function addData($prefix, $content)
	{
		if (is_array($content) || is_object($content)) {
			$content = json_encode($content);
		}
		return $this->addAttr('data-' . $prefix, $content);
	}

	public function addId()
	{
		foreach (func_get_args() as $id) {
			$this->addAttr('id', $id);
		}
		return $this;
	}

	public function addStyle($key, $value)
	{
		$this->addAttr('style', sprintf('%s: %s;', $key, $value));
		return $this;
	}

	public function getAttr()
	{
		$html = '';
		foreach ($this->attributes as $name => $values) {
			$html .= ' ' . htmlspecialchars($name) . '="' . htmlspecialchars(join(' ', $values)) . '"';
		}
		return $html;
	}

	public function getChild()
	{
		$html = '';
		foreach ($this->children as $child) {
			$html .= (string)$child;
		}
		return $html;
	}
}