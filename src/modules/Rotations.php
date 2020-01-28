<?php

namespace MidPay;

class Rotations
{
	const WEIGHTED_RANDOM = 'WR';
	const WEIGHTED_ROUND_ROBIN = 'WRR';
	const HIGHEST_SUCCESS_RATE_WITH_AGING = 'HSRA';

	/**
	 * Returns the current algorithm's parameters 
	 * for the current user.
	 * @return array
	 */
	private static function _rotation()
	{
		$rows = Db::select('rotations', array(
			'key', 
			'value'
		), array(
			'user_id' => Auth::userId()
		));

		$rotation = array();
		foreach ($rows as $row) {
			$rotation[$row['key']] = $row['key'];
		}
	}

	/**
	 * Returns a list of channels for the current user.
	 * @return array 
	 */
	private static function _channels()
	{
		$rows = Db::query('SELECT DISTINCT ' .
			implode(', ', array(
				'channels.gateway_id',
				'channels.channel',
				'channels.ping',
				'channels.rotation_weight',
				'channels.type',
			)) . ' FROM ' .
			implode(', ', array(
				'channels',
				'group_channels',
				'user_groups',
				'gateways',
			)) . ' WHERE ' .
			implode(' AND ', array(
				'channels.gateway_id = group_channels.gateway_id',
				'channels.channel = group_channels.channel',
				'channels.enabled = 1',

				'gateways.gateway_id = channels.gateway_id',
				'gateways.enabled = 1',

				'user_groups.group_id = group_channels.group_id',
				'user_groups.activated = 1',

				'user_groups.user_id = \'' . Auth::userId() . '\''
			)) . ' ORDER BY ' .
			implode(', ', array(
				'channels.rotation_weight DESC',
				'channels.gateway_id ASC',
				'channels.channel ASC'
			))
		);
		return Utils::assocRows($rows);
	}


	private static function _weightedRoundRobinOrder($rows)
	{
		$ordered = array();
		$start = 0;
		$minWeight = 1;
		$maxWeight = 1;

		foreach ($rows as $row) {
			if ($row['rotation_weight'] >= $minWeight) {
				$ordered[] = $row;
				if ($row['rotation_weight'] > $maxWeight)
					$maxWeight = $row['rotation_weight'];
			}
		}
		
		while ($minWeight < $maxWeight) {
			$end = count($ordered);
			$minWeight++;
			for ($i = $start; $i < $end; ++$i) {
				if ($ordered[$i]['rotation_weight'] >= $minWeight)
					$ordered[] = $ordered[$i];
			}
			$start = $end;
		}
		return $ordered;
	}

	private static function _weightedRandomSample($rows)
	{
		$weightSum = 0;
		foreach ($rows as $row) {
			$weightSum += (float) $row['rotation_weight'];
		}
		$r = mt_rand() / mt_getrandmax();
		foreach ($rows as $row) {
			if ($r < 1.0)
				return $row;
			$r -= (float) $row['rotation_weight'];
		}
		return $rows[count($rows) - 1];
	}

	private static function _highestSuccessRateWithAging($rows, $numPast, $ageWeight)
	{
		$queries = array();
		foreach ($rows as $i => $row) {
			// If there are no entries:
			// num_success will be null,
			// count will be 0,
			// last_attempted will be null
			$queries[] = 'SELECT ' .
			implode(', ', array(
				'SUM(success) AS num_success', 
				'COUNT(*) AS count',
				'MAX(created) AS last_attempted', 
				'\'' . $i . '\' AS i'
			)) . ' FROM channel_attempts WHERE ' .
			implode(' AND ', array(
				'channel = \'' . $row['channel'] . '\'', 
				'gateway_id = \'' . $row['gateway_id'] . '\''
			) . ' LIMIT ' . min(1000, $numPast));
		}

		$statRows = Db::query(implode(' UNION ALL ', $queries));

		$now = Timestamp::now();
		$maxFactor = 0;
		$bestIndex = 0;
		foreach ($statRows as $statRow) {
			$i = (int)$statRow['i'];
			$count = (int)($statRow['count']);
			$successRate = $count < 1 ? 
				1.0 : 
				(float)($statRow['num_success']) / (float)($count);
			$elapsed = $count < 1 ? 
				0.0 : 
				$now - $statRow['last_attempted'];
			// Should we normalize elapsed?
			$factor = (1.0 - $ageWeight) * $successRate + 
				$ageWeight * $elapsed;
			if ($factor > $maxFactor) {
				$bestIndex = $i;
				$maxFactor = $factor;
			}
		}
		return $rows[$bestIndex];
	}

	private static function _setKeyValues($keyValues)
	{
		Db::delete('rotations', array(
			'user_id' => Auth::userId()
		));
		$rows = array();

		foreach ($keyValues as $key => $value) {
			$rows[] = array(
				'user_id' => Auth::userId(),
				'key' => $key,
				'value' => $value
			);
		}
		Db::insert('rotations', $rows);
	}


	public static function nextAttempt()
	{
		$rotation = self::_rotation();

		$row = null;
		switch ($algo = $rotation['algo']) {

			case self::WEIGHTED_RANDOM:
				$rows = self::_channels();
				$row = self::_weightedRandomSample($rows);
				break;

			case self::WEIGHTED_ROUND_ROBIN:
				$rows = self::_weightedRoundRobinOrder(self::_channels());
				$currentIndex = (int)$rotation[$algo.':index'];
				$row = $rows[$currentIndex];
				$currentIndex = ($currentIndex + 1) % count($rows);
				Db::update('rotations', array(
					$algo.':index' => $currentIndex
				), array(
					'user_id' => Auth::userId()
				));
				break;

			case self::HIGHEST_SUCCESS_RATE_WITH_AGING:
				$rows = self::_channels();
				$row = self::_highestSuccessRateWithAging($rows,
					(int)$rotation[$algo.':numPast'],
					(float)$rotation[$algo.':ageWeight']
				);
				break;
		}

		$attemptId = Crypto::randUniqueString('channel_attempts', 'attempt_id');
		Db::insert('channel_attempts', array(
			'attempt_id' => $attemptId,
			'gateway_id' => $row['gateway_id'],
			'channel' => $row['channel'],
			'created' => Timestamp::now(),
			'success' => 0
		));

		return array(
			'attemptId' => $attemptId,
			'gatewayId' => $row['gateway_id'],
			'channel' => $row['channel'],
		);
	}

	/**
	 * Changes the rotation algorithm for the user to weighted random.
	 */
	public static function setWeightedRandom()
	{
		self::_setKeyValues(array(
			'algo' => $algo = self::WEIGHTED_RANDOM,
		));
	}

	/**
	 * Changes the rotation algorithm for the user to weighted round robin.
	 */
	public static function setWeightedRoundRobin()
	{
		self::_setKeyValues(array(
			'algo' => $algo = self::WEIGHTED_ROUND_ROBIN,
			$algo.':index' => 0
		));
	}

	/**
	 * Changes the rotation algorithm for the user to 
	 * highest success rate with aging.
	 * @param integer $numPast   Number of past attempts to consider.
	 * @param integer $ageWeight The weight to weight the last elapsed 
	 *                           time for channel attempts.
	 */
	public static function setHighestSuccessRateWithAging($numPast, $ageWeight)
	{
		self::_setKeyValues(array(
			'algo' => $algo = self::HIGHEST_SUCCESS_RATE_WITH_AGING, 
			$algo.':numPast' => $numPast, 
			$algo.':ageWeight' => $ageWeight
		));
	}

	/**
	 * Must be called whenever admin changes the weight of any gateway.
	 */
	public static function invalidateWeightedRoundRobins()
	{
		$algo = self::WEIGHTED_ROUND_ROBIN;
		Db::update('rotations', array(
			'value' => 0
		), array(
			'key' => $algo.':index' 
		));
	}

	/**
	 * Close the attempt.
	 * An attempt is considered to be successful if and only if
	 * it is completed on the gateway vendor's side.
	 * @param  string  $attemptId The attempt's id.
	 * @param  boolean $success   Whether the attempt succeeded.
	 */
	public static function closeAttempt($attemptId, $success)
	{
		Db::update('channel_attempts', array(
			'success' => $success,
			'closed' => Timestamp::now()
		));
	}
}