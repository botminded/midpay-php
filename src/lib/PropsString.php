<?php

namespace MidPay;

class PropsString
{
	protected $str;
	protected $props;

	public function __construct($str, $updates=array()) 
	{
		if ($str instanceof self) {
			$this->str = $str->str;
			$this->props = array_merge_recursive($str->props, $updates);
		} else {
			$this->str = $str;
			$this->props = $updates;
		}
	}

	public function __toString() 
	{
		return $this->str;
	}

	public function has($key) 
	{
		return isset($this->props[$key]);
	}

	public function get($key, $default=NULL) 
	{
		return isset($this->props[$key]) ? $this->props[$key] : $default;
	}

	public function set($key, $value) 
	{
		$this->props[$key] = $value;
	}

	public function val($new_val=NULL)
	{
		if (is_string($new_val)) {
			return $this->str = $new_val;
		} 
		return $this->str;
	}
}