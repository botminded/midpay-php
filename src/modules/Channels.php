<?php

namespace MidPay;

class Channels
{
	const TYPE_DEPOSITS = 'DEPOSITS';
	const TYPE_WITHDRAWALS = 'WITHDRAWALS';

	const METHOD_WECHAT = 'WeChat';
	const METHOD_ALIPAY = 'AliPay';
	const METHOD_UNION_QUICK = 'UnionQuick';

	private static function _coerce($value, $prefix) 
	{
		static $cache;
		if (!isset($cache)) $cache = array();
		if (!isset($cache[$prefix][$value])) 
			$cache[$prefix][$value] = Enums::coerce(
				Enums::from(__CLASS__, $prefix),
				$value,
				Enums::COERCE_VALUE_TO_VALUE
			);
		return $cache[$prefix][$value];
	}

	public static function coerceType($value) 
	{
		return self::_coerce($value, 'TYPE');
	}

	public static function coerceMethod($value)
	{
		return self::_coerce($value, 'METHOD');
	}

}
