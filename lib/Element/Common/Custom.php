<?
namespace Asenine\Element;

class Custom extends Common\Root
{
	protected
		$name = '';

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function __toString()
	{
		return sprintf('<%s %s>%s</%s>', $this->name, $this->getAttributes(), $this->stringChildren(), $this->name);
	}
}