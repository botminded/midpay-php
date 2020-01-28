<?php

namespace MidPay;

include('../lib.php');
include('../modules.php');

Db::beginTransaction();

$userId = Crypto::randUniqueString('users', 'user_id');
$password = Crypto::randString(15);
$apiKey = Crypto::randUniqueString('users', 'api_key');

Db::insert('users', array(
	'user_id' => $userId,
	'password' => Crypto::hashPassword($password),
	'api_key' => $apiKey,
	'balance' => '0.00',
	'data' => '',
	'type' => 'OPERATOR'
));

$gateways = array(
	'GATEWAY_0_' . Crypto::randString(10) => array(
		'A',
		'B',
		'C'
	),
	'GATEWAY_1_' . Crypto::randString(10) => array(
		'A',
		'B',
		'C',
		'D'
	),
	'GATEWAY_2_' . Crypto::randString(10) => array(
		'B',
		'C',
		'D'
	)
);

$w = 1;
foreach ($gateways as $gatewayId => $channels) {
	Db::insert('gateways', array(
		'gateway_id' => $gatewayId,
		'enabled' => 1,
	));

	foreach ($channels as $channel) {
		Db::insert('channels', array(
			'gateway_id' => $gatewayId,
			'channel' => $channel,
			'ping' => 1,
			'enabled' => 1,
			'rotation_weight' => $w++,
			'type' => 'DEPOSIT',
		));
	}
}
$gatewayIds = array_keys($gateways);

$groups = array(
	'GROUP_A_' . Crypto::randString(10) => array(
		array(
			'gateway_id' => $gatewayIds[0],
			'channel' => $gateways[$gatewayIds[0]][0]
		),
		array(
			'gateway_id' => $gatewayIds[0],
			'channel' => $gateways[$gatewayIds[0]][2]
		),
		array(
			'gateway_id' => $gatewayIds[2],
			'channel' => $gateways[$gatewayIds[2]][1]
		)
	),
	'GROUP_B_' . Crypto::randString(10) => array(
		array(
			'gateway_id' => $gatewayIds[2],
			'channel' => $gateways[$gatewayIds[2]][0]
		),
		array(
			'gateway_id' => $gatewayIds[1],
			'channel' => $gateways[$gatewayIds[1]][2]
		),
		array(
			'gateway_id' => $gatewayIds[2],
			'channel' => $gateways[$gatewayIds[2]][1]
		)
	),
	'GROUP_C_' . Crypto::randString(10) => array(
		array(
			'gateway_id' => $gatewayIds[2],
			'channel' => $gateways[$gatewayIds[2]][0]
		),
		array(
			'gateway_id' => $gatewayIds[1],
			'channel' => $gateways[$gatewayIds[1]][2]
		),
		array(
			'gateway_id' => $gatewayIds[2],
			'channel' => $gateways[$gatewayIds[2]][1]
		),
		array(
			'gateway_id' => $gatewayIds[2],
			'channel' => $gateways[$gatewayIds[2]][2]
		)
	)
);
$groupIds = array_keys($groups);

foreach ($groups as $groupId => $channels) {
	Db::insert('groups', array(
		'group_id' => $groupId
	));

	foreach ($channels as $channel) {
		Db::insert('group_channels', array(
			'group_id' => $groupId,
			'gateway_id' => $channel['gateway_id'],
			'channel' => $channel['channel']
		));
	}
}

$userGroups = array(
	$groupIds[0],
	$groupIds[1],
	$groupIds[2]
);

foreach ($userGroups as $groupId) {
	Db::insert('user_groups', array(
		'user_id' => $userId,
		'group_id' => $groupId,
		'activated' => 1
	));
}



// OK, now we wanna make a query that will collect all the channels


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

		'user_groups.user_id = \'' . $userId . '\''
	)) . ' ORDER BY ' .
	implode(', ', array(
		'channels.rotation_weight ASC',
		'channels.gateway_id ASC',
		'channels.channel ASC'
	))
);

$rows = Utils::assocRows($rows);

$attemptIds = array();

for ($i = 0; $i < 100; ++$i) {
	$selected = $rows[rand(0, count($rows) - 1)];
	$closed = Timestamp::now() - rand(0, 3600);
	Db::insert('channel_attempts', array(
		'attempt_id' => $attemptId = Crypto::randString(10), 
		'order_id' => Crypto::randString(10), 
		'gateway_id' => $selected['gateway_id'],
		'channel' => $selected['channel'],
		'created' => $closed - rand(0, 600),
		'closed' => $closed,
		'success' => (rand(0, 99) < 70)
	));
	$attemptIds[] = $attemptId;
}

$queries = array();
foreach ($rows as $i => $row) {
	$queries[] = 'SELECT ' .
	implode(', ', array(
		'SUM(success) AS num_success', 
		'COUNT(*) AS count',
		'MAX(created) AS elapsed', 
		'\'' . $i . '\' AS i'
	)) . ' FROM channel_attempts WHERE ' .
	implode(' AND ', array(
		'channel = \'' . $row['channel'] . '\'', 
		'gateway_id = \'' . $row['gateway_id'] . '\''
	));
}

$stats = Db::query(implode(' UNION ALL ', $queries));
var_dump(Utils::assocRows($stats));


// Cleanup
if (0) {
	foreach ($attemptIds as $attemptId) {
		Db::delete('channel_attempts', array(
			'attempt_id' => $attemptId
		));
	}
	foreach ($groups as $groupId => $channels) {
		Db::delete('groups', array(
			'group_id' => $groupId,
		));

		foreach ($channels as $channel) {
			Db::delete('group_channels', array(
				'group_id' => $groupId,
				'gateway_id' => $channel['gateway_id'],
				'channel' => $channel['channel'],
			));
		}
	}

	foreach ($gateways as $gatewayId => $channels) {
		Db::delete('gateways', array(
			'gateway_id' => $gatewayId,
		));

		foreach ($channels as $channel) {
			Db::delete('channels', array(
				'gateway_id' => $gatewayId,
				'channel' => $channel,
			));
		}
	}

	Db::delete('users', array(
		'user_id' => $userId
	));

	foreach ($userGroups as $groupId) {
		Db::delete('user_groups', array(
			'user_id' => $userId,
			'group_id' => $groupId
		));
	}


	echo "\n";	
}

Db::commit();