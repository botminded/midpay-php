<?php

/**
 * A static class for creating callbacks.
 */
class Callback
{	
	private static function _gen_id() 
	{
		for ($i = 0; $i < 99; $i++) 
			if (!DB::has('callbacks', array('id' => $r = Crypto::rand_base36(31)))) {
				return $r;
			}
		return '';
	}

	public static function create($callback, $data,
		$num_retries=8,
		$interval_growth_exponent=2,
		$interval_initial_value=2)
	{
		// For testing purposes.
		Response::set('callback', $callback);
		Response::set('callback_data', $data);

		if (Params::is_not_empty($callback)) {
			$serialized = json_encode(array(
				'callback' => $callback, 
				'data' => $data,
				'interval_growth_exponent' => $interval_growth_exponent
			));
			$now = Timestamp::now();

			DB::insert('callbacks', array(
				'id' => ($id = Callback::_gen_id()),
				'retries_left' => $num_retries + 1,
				'last_attempt' => $now - $interval_initial_value,
				'next_attempt' => $now,
				'serialized' => $serialized
			));
			return $id;	
		}
		return '';
	}
}