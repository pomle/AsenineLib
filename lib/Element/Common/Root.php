<?
namespace Asenine\Element\Common;

interface iRoot
{
	public function __toString();
}

abstract class Root implements iRoot
{
	protected
		$attributes = array(),
		$children = array(),
		$styles = array(),
		$tag = 'div';


	public function __toString()
	{
		return $this->getStartTag() . $this->stringChildren() . $this->getEndTag();
	}


	public static function asWrapper()
	{
		$Object = new static();
		$Object->addChildren(func_get_args());
		return $Object;
	}


	public function addAttr($key, $value)
	{
		$this->attributes[(string)$key][] = (string)$value;
		return $this;
	}

	public function addChild($Element)
	{
		$this->children[] = $Element;
		return $this;
	}

	public function addChildren($array)
	{
		foreach(is_array($array) ? $array : func_get_args() as $Element)
			$this->addChild($Element);

		return $this;
	}

	public function addClass($class)
	{
		return $this->addAttr('class', $class);
	}

	public function addClasses($array)
	{
		foreach(is_array($array) ? $array : func_get_args() as $class)
			$this->addClass($class);

		return $this;
	}

	public function addData($prefix, $content)
	{
		if (is_array($content) || is_object($content)) {
			$content = json_encode($content);
		}
		return $this->addAttr('data-' . $prefix, $content);
	}

	public function addID($string)
	{
		$this
			->removeAttr('id')
			->addAttr('id', $string);

		return $this;
	}

	public function addHtml($string)
	{
		$this->addChild(htmlspecialchars((string)$string));
		return $this;
	}

	public function addStyle($key, $value)
	{
		$this->styles[$key] = $value;
		return $this;
	}

	public function ensureAttr($key, $value, $state)
	{
		if( $state == false && $this->hasAttr($key, $value) )
			$this->removeAttr($key, $value);

		elseif( $state == true && !$this->hasAttr($key, $value) )
			$this->addAttr($key, $value);

		return $this;
	}

	public function ensureClass($class, $state)
	{
		return $this->ensureAttr('class', $class, $state);
	}

	public function hasAttr($key, $value = null)
	{
		if(!isset($this->attributes[$key]))
			return false;

		if($value && !in_array($value, $this->attributes[$key]))
			return false;

		return true;
	}

	public function hasClass($class)
	{
		return $this->hasAttr('class', $class);
	}

	public function hasID($string = null)
	{
		return $this->hasAttr('id', $string);
	}

	public function getAttributes()
	{
		$string = '';

		foreach($this->attributes as $attribute => $values)
			$string .= sprintf(' %s="%s"', htmlspecialchars($attribute), htmlspecialchars(join(' ', $values)));

		if(count($this->styles))
		{
			$string .= ' style="';

			foreach($this->styles as $key => $value)
				$string .= htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '; ';

			$string .= '"';
		}

		return $string;
	}

	public function getChildren()
	{
		return $this->children;
	}

	final public function getStartTag()
	{
		return sprintf('<%s %s>', $this->tag, $this->getAttributes());
	}

	final public function getEndTag()
	{
		return sprintf('</%s>', $this->tag);
	}


	final public function printStartTag()
	{
		echo $this->getStartTag();
	}

	final public function printEndTag()
	{
		echo $this->getEndTag();
	}

	public function removeAttr($key, $value = null)
	{
		if(!is_null($value) && ($index = array_search($value, $this->attributes[$key])) !== false )
			unset($this->attributes[$key][$index]);
		else
			unset($this->attributes[$key]);

		return $this;
	}

	public function removeData($key)
	{
		$this->removeAttr('data-' . $key);
		return $this;
	}

	public function stringChildren()
	{
		return join('', $this->getChildren());
	}
}