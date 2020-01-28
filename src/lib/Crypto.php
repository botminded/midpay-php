<?php

namespace MidPay;

include(realpath(dirname(__FILE__)).'/external/phpass.php');

/**
 * A class containing static methods for cryptographic operations.
 */
class Crypto
{

	const BASE_36_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const BASE_64_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	const BASE_64_FILE_SAFE_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

	private static function _hasher() 
	{
		static $hasher_instance;
		if (isset($hasher_instance)) 
			return $hasher_instance;
		$hasher_instance = new \PasswordHash(8, false);
		return $hasher_instance;
	}

	/**
	 * Hashes a password to be stored in the database.
	 * @param  string  $password The plaintext password to be hashed.
	 * @return string            The hashed password.
	 */
	public static function hashPassword($password) 
	{
		return self::_hasher()->HashPassword($password);
	}

	/**
	 * Checks a password against a stored hash.
	 * @param  string  $password    The plaintext password.
	 * @param  string  $stored_hash The hashed password.
	 * @return boolean              True if the passwords match, else False.
	 */
	public static function checkPassword($password, $stored_hash) 
	{
		return self::_hasher()->CheckPassword($password, $stored_hash);
	}

	/**
	 * Generates a cryptographically secure random string from the characters.
	 * @param  int    $length The length of the string.
	 * @param  string $chars  The characters to choose from.
	 * @return string         The generated string.
	 */
	public static function randString($length, $chars=self::BASE_36_CHARS) 
	{
		$s = '';
		$j = $l = (int) (($length = $length < 0 ? 0 : $length) * 1.1);
		$end = ($num_chars = strlen($chars)) * (int) (255 / $num_chars);
		while (strlen($s) < $length) {
			if ($j >= $l) {
				$bytes = self::_hasher()->get_random_bytes($l);
				$j = 0;
			}
			if (($o = ord($bytes[$j++])) < $end) $s .= $chars[$o % $num_chars];
		}
		return $s;
	}

	/**
	 * Generates a cryptographically secure random string that does not
	 * exist in the field of the table.
	 * @param  string $table  The table name.
	 * @param  string $field  The field name.
	 * @param  int    $length The length of the random string. Defaults to the field's max Length.
	 * @param  string $chars  The characters to choose from. Defaults to BASE_36_CHARS.
	 * @return string         The generated string.
	 */
	public static function randUniqueString($table, $field, 
		$length=null, $chars=self::BASE_36_CHARS)
	{
		if (is_null($length)) {
			$schema = Db::schema($table);
			if (isset($schema[$field])) {
				$length = $schema[$field]->maxLength;
			}
			if ($length < 1) 
				return '';
		}
		for ($i = 0; $i < 99; $i++) {
			if (!Db::has($table, array($field => $r = self::randString($length, $chars))))
				return $r;
		}
		return '';
	}

}