<?php

namespace MidPay;

class Logs
{
	public static function insert($type, $tag, $message)
	{
		Db::insert('logs', array(
			'log_id' => $logId = Crypto::randUniqueString('logs', 'log_id'),
			'type' => $type,
			'created' => Timestamp::now(),
			'tag' => $tag,
			'message' => $message,
		));
		return $logId;
	}
};
