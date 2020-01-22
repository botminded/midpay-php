<?php

/**
 * A static class for logging worker and requestor activity.
 */
class Log
{
	const USER_WORKER = 1;
	const USER_REQUESTOR = 11;

	/**
	 * Logs a user's action.
	 * @param string  $username The worker's username.
	 * @param integer $type     The type of user.
	 * @param string  $comment  The comment for the log.
	 */
	public static function log_user($username, $type, $comment)
	{
		DB::insert('logs', array(
			'type' => $type,
			'created' => Timestamp::now(),
			'tag' => $username,
			'comment' => $comment
		));	
	}

	/**
	 * Retrieve all the user logs.
	 * @param  integer $type  The type of user.
	 * @param  integer $limit [description]
	 * @return array          A list of the user logs.
	 */
	public static function user_logs($type, $limit=1000) 
	{
		$rows = DB::select('logs', array(
			'tag', 'created', 'comment'
		), array(
			'type' => $type,
			'ORDER' => 'id DESC',
			'LIMIT' => $limit
		));
		
		foreach ($rows as $i => $r) {
			$r['username'] = $r['tag'];
			$r['timestamp'] = $r['created'];
			unset($r['tag'], $r['created']);
			$rows[$i] = $r;
		}
		return $rows;	
	}
};
