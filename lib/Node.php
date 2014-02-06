<?
namespace Asenine
{
	class Node
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
			$Attr = new Node\Attr($key, $value);
			$this->attributes[(string)$key][] = $Attr;
			return $Attr;
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
			return $this->addAttr('style', sprintf('%s: %s;', $key, $value));
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
}

namespace Asenine\Node
{
	class Attr
	{
		public $name;
		public $value;

		public function __construct($name, $value)
		{
			$this->name = $name;
			$this->value = $value;
		}

		public function __toString()
		{
			if (is_array($this->value) || $this->value instanceof \stdClass) {
				$value = json_encode($this->value);
			}
			else {
				$value = (string)$this->value;
			}

			return (string)$value;
		}
	}
}