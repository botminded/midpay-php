<?php

namespace MidPay;

class Adapters
{	
	private static function _joinPaths($paths)
	{
		$comps = array();
		foreach ($paths as $p) {
			$comps[] = trim($p, '/');
		}
		return implode('/', array_filter($paths));
	}

	private static function _runPHP($_path, $IN, &$OUT)
	{
		// For php, it is better to simply include it.
		// Running a new command line process has an overhead of around .04s
		include($_path);
	}

	private static function _adaptersPath()
	{
		return realpath(dirname(__FILE__)).'/../adapters';
	}

	public static function run($adapterPath, $inputs, $outputFields)
	{
		$cwd = getcwd();
		$IN = $inputs;
		$OUT = array();
		foreach ($outputFields as $field) {
			$OUT[$field] = null;
		}
		chdir(self::_adaptersPath());
		self::_runPHP($adapterPath, $IN, $OUT);
		chdir($cwd);

		return $OUT;
	}

	private static function _prepConfigTypeInfo($dir, &$info)
	{
		foreach (array('create', 'check', 'callback') as $a) {
			if (isset($info[$a]) && is_string($info[$a])) {
				if (substr($info[$a], 0, 1) != '/')
					$info[$a] = rtrim($dir, '/') . '/' . $info[$a];
				if (substr($info[$a], 0, 2) == './')
					$info[$a] = substr($info[$a], 2);
			} else {
				unset($info[$a]);
			}
		}

		if (!(isset($info['channels']) && is_array($info['channels']))) 
			$info['channels'] = array();
		
		$channels = array();
		
		if (count(array_filter(array_keys($info['channels']), 'is_string'))) {
			foreach ($info['channels'] as $k => $v) 
				if (!(is_array($v) && isset($v['exists']) && !$v['exists']))
					$channels[] = $k;			
			$info['channels'] = $channels;
		} 
	}

	private static function _scanDirAddConfig($dir, 
		$configPath, &$configs)
	{
		$config = json_decode(file_get_contents($configPath), 1);
		
		if (is_null($config) || 
			!is_array($config) || 
			!isset($config['gatewayId'])) 
			return;

		if (isset($config['exists']) && !$config['exists'])
			return;
	
		if (!isset($config['name']))
			$config['name'] = '';
		
		if (!isset($config['description']))
			$config['description'] = '';

		$dir = rtrim($dir, '/') . '/';

		foreach (array('deposits', 'withdrawals') as $k) {
			if (isset($config[$k]) && is_array($config[$k])) {
				self::_prepConfigTypeInfo($dir, $config[$k]);
			}
		}
		$configs[$config['gatewayId']] = $config;
	}

	private static function _scanDir($dir, &$configs)
	{
		foreach (scandir($dir) as $c) {
			$d = $dir . '/' . $c;
			if ($c != '.' && $c != '..' && is_dir($d)) { 
				// if it's a directory, recurse.
				self::_scanDir($d, $configs);
			} else if (strtolower(pathinfo($c, PATHINFO_EXTENSION)) == 'json') {
				// if it's a config json, let's see what it is.
				self::_scanDirAddConfig($dir, $d, $configs);
			}
		}
	}

	private static function _sync($configs)
	{
		$sep = ':' . chr(1);

		$dbChannels = Db::select('channels', '*');

		$exists['db'] = array();
		$exists['config'] = array();
		
		foreach ($dbChannels as $r) {
			$exists['db'][$r['gateway_id']] = 1;
			$exists['db'][$r['gateway_id'] . $sep . 
				$r['channel'] . $sep . $r['type']] = 1;
		}
		
		foreach ($configs as $gatewayId => $config) {
			$exists['config'][$gatewayId] = 1;
			foreach (array('deposits', 'withdrawals') as $type) {
				foreach ($config[$type]['channels'] as $channel) {
					$exists['config'][$gatewayId . $sep. 
						$channel . $sep . 
						Channels::coerceType($type)] = 1;
				}
			}
		}

		foreach ($dbChannels as $r) {
			if (!isset($exists['config'][$r['gateway_id']])) {
				Db::update('gateways', array(
					'exists' => 0
				), array(
					'gateway_id' => $r['gateway_id']
				));
			}
			if (!isset($exists['config'][$r['gateway_id'] . $sep . 
				$r['channel'] . $sep . $r['type']])) {
				Db::update('channels', array(
					'exists' => 0
				), array(
					'gateway_id' => $r['gateway_id'],
					'channel' => $r['channel'],
					'type' => $r['type'],
				));
			}
		}

		foreach ($configs as $gatewayId => $config) {
			if (!isset($exists['db'][$gatewayId])) {
				Db::insert('gateways', array(
					'gateway_id' => $gatewayId
				));
			}
			foreach (array('deposits', 'withdrawals') as $type) {
				foreach ($config[$type]['channels'] as $channel) {
					if (!isset($exists['db'][$gatewayId . $sep. 
						$channel . $sep . $type])) {
						Db::insert('channels', array(
							'gateway_id' => $gatewayId,
							'channel' => $channel,
							'type' => Channels::coerceType($type)
						));
					}
				}
			}
		}

	}

	public static function foo()
	{
		$cwd = getcwd();
		chdir(self::_adaptersPath());
		$configs = array();
		self::_scanDir('.', $configs);
		self::_sync($configs);
		chdir($cwd);		
	}
}
/*
 * - scan all the jsons, build up the mapping.
 * - for each gateway
 *    - if the gateway exists, 
 *    - get all channels from the DB
 */