<?
namespace Asenine\Mail;

class Mail
{
	public $from;
	public $replyTo;
	public $to = array();
	public $cc = array();
	public $bcc = array();
	public $subject = '';
	public $body = '';


	public function __construct()
	{}

	public function __toString()
	{
		return join("\n", $this->_headers()) . "\n"
		. "To: " . join(", ", $this->to) . "\n"
		. "Subject: " . $this->_subject() . "\n"
		. $this->_body();
	}



	public function address($address, $name = null)
	{
		if ($name) {
			return sprintf('%s <%s>', $name, $address);
		}
		else {
			return $address;
		}
	}

	public function bcc($address, $name = null)
	{
		$this->bcc[] = $this->address($address, $name);
		return $this;
	}

	public function body($text)
	{
		$args = func_get_args();
		$text = array_shift($args);
		if (count($args)) {
			$text = vsprintf($text, $args);
		}
		$this->body = $text;
		return $this;
	}

	public function cc($address, $name = null)
	{
		$this->cc[] = $this->address($address, $name);
		return $this;
	}

	public function from($address, $name = null)
	{
		$this->from = $this->address($address, $name);
		return $this;
	}

	public function replyTo($address, $name = null)
	{
		$this->replyTo = $this->address($address, $name);
		return $this;
	}

	public function subject($text)
	{
		$args = func_get_args();
		$text = array_shift($args);
		if (count($args)) {
			$text = vsprintf($text, $args);
		}
		$this->subject = $text;
		return $this;
	}

	public function to($address, $name = null)
	{
		$this->to[] = $this->address($address, $name);
		return $this;
	}

	public function send()
	{
		return mail(join(',', $this->to), $this->_subject(), $this->_body(), join("\r\n", $this->_headers()));
	}


	protected function _body()
	{
		return $this->body;
	}

	protected function _headers()
	{
		$headers = array();
		if ($this->from) {
			$headers[] = 'From: ' . $this->from;
		}
		if ($this->replyTo) {
			$headers[] = 'Reply-To: ' . $this->replyTo;
		}
		if ($this->cc) {
			$headers[] = 'Cc: ' . join(',', $this->cc);
		}
		if ($this->bcc) {
			$headers[] = 'Bcc: ' . join(',', $this->bcc);
		}
		return $headers;
	}

	protected function _subject()
	{
		return $this->subject;
	}
}