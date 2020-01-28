DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS groups;
DROP TABLE IF EXISTS gateways;
DROP TABLE IF EXISTS channels;
DROP TABLE IF EXISTS user_groups;
DROP TABLE IF EXISTS group_channels;
DROP TABLE IF EXISTS rotations;
DROP TABLE IF EXISTS key_values;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS channel_attempts;
DROP TABLE IF EXISTS callbacks;
DROP TABLE IF EXISTS logs;

/* See: */
/* https://stackoverflow.com/questions/4169893/is-it-good-database-design-to-have-admin-users-in-the-same-table-as-front-end-us */
/* http://komlenic.com/244/8-reasons-why-mysqls-enum-data-type-is-evil/ */

CREATE TABLE users (
	`user_id` CHAR(31) NOT NULL PRIMARY KEY,
	`password` CHAR(63),
	`api_key` CHAR(31),
	`balance` NUMERIC(31, 4) NOT NULL DEFAULT 0,
	`data` TEXT /* json for other information */,
	`type` CHAR(31) NOT NULL /* enum: ADMIN, OPERATOR */
);
CREATE UNIQUE INDEX `users_api_key_index` ON `users`(`api_key`);

CREATE TABLE sessions (
	`session_id` CHAR(31) NOT NULL PRIMARY KEY, /* session id */
	`created` BIGINT DEFAULT 0 /* (timestamp) */,
	`user_id` CHAR(31) NOT NULL /* who owns this session */
);

CREATE TABLE groups (
	`group_id` CHAR(31) NOT NULL PRIMARY KEY /* try to give meaningful name for id */,
	`data` TEXT /* json for other information */
);

CREATE TABLE gateways (
	`gateway_id` CHAR(31) NOT NULL PRIMARY KEY /* try to give meaningful name for id */,
	`data` TEXT /* json for other information */,
	`enabled` BOOLEAN DEFAULT 1
);

CREATE TABLE channels (
	`gateway_id` CHAR(31) NOT NULL,
	`channel` CHAR(15) NOT NULL /* channel */,
	
	`ping` BOOLEAN DEFAULT 1 /* whether it can be pinged */,
	
	`enabled` BOOLEAN DEFAULT 1, 
	`rotation_weight` INTEGER DEFAULT 1,
	`type` CHAR(31) /* enum: DEPOSIT, WITHDRAW */,  
	`data` TEXT /* json for other information */
);
CREATE UNIQUE INDEX `channels_index` ON `channels`(`gateway_id`, `channel`);

CREATE TABLE user_groups (
	`user_id` CHAR(31) NOT NULL,
	`group_id` CHAR(31) NOT NULL,
	`activated` BOOLEAN DEFAULT 1 
);

/* we make a distinction between activated and enabled */
/* activated: for the account */
/* enabled: globally */

CREATE TABLE group_channels (
	`group_id` CHAR(31) NOT NULL,

	`gateway_id` CHAR(31) NOT NULL,
	`channel` CHAR(15) NOT NULL /* channel */
	
);

/* key value store for rotation algorithm variables */
CREATE TABLE rotations (
	`user_id` CHAR(31) NOT NULL, 
	`key` CHAR(31) NOT NULL, 
	`value` CHAR(63)
); 
CREATE INDEX `rotations_user_id_index` ON `rotations`(`user_id`);
CREATE INDEX `rotations_key_index` ON `rotations`(`key`);

/* key value store for other variables */
CREATE TABLE key_values (
	`key` CHAR(31) NOT NULL PRIMARY KEY,
	`value` CHAR(63)	
);

CREATE TABLE orders (
	`order_id` CHAR(31) NOT NULL PRIMARY KEY /* auto generated */,
	`user_id` CHAR(31) NOT NULL /* user id of the operator who requested this */,
	`user_order_id` CHAR(31) NOT NULL /* provided by the operator */,

	`attempt_id` CHAR(31) NOT NULL,
	
	`gateway_id` CHAR(31) NOT NULL /* dynamically assigned via algo */,
	`channel` CHAR(15) NOT NULL /* dynamically assigned via algo */,
	
	`status` CHAR(31),
	
	`amount` NUMERIC(31, 4) NOT NULL,
	
	`data` TEXT /* json for other information (callback, ip, back) */,
	
	/* When is the order forwarded to the gateway */
	`created` BIGINT DEFAULT 0 /* (timestamp) */, 
	/* When is the callback received from the gateway */
	`closed` BIGINT DEFAULT 0 /* (timestamp) */, 
	/* Number of pings used to get the result. */
	`num_pings` INTEGER DEFAULT 0,

	`auth_log_id` CHAR(31)


);
CREATE UNIQUE INDEX `user_order_index` ON `orders`(`user_id`, `user_order_id`);


/* we have a special table just for channel attempts */
/* cuz a single order from operator can result in attempts to multiple gateway channels */
/* having a single table makes computing the success rates faster */
CREATE TABLE channel_attempts (
	`attempt_id` CHAR(31) NOT NULL PRIMARY KEY,
	`order_id` CHAR(31) NOT NULL,

	`gateway_id` CHAR(31) NOT NULL,
	`channel` CHAR(15) NOT NULL,

	/* The created also doubles as a last used value for the gateway */
	`created` BIGINT DEFAULT 0 /* (timestamp) */, 
	`closed` BIGINT DEFAULT 0 /* (timestamp) */, 

	/* Ok, we have to denormalize a bit here so that */
	/* we don't need a join to compute success rates */
	
	/* whether success */
	`success` BOOLEAN 
);

CREATE TABLE callbacks (
	`callback_id` CHAR(31) NOT NULL PRIMARY KEY,
	`retries_left` INT NOT NULL DEFAULT 0,
	`last_attempt` BIGINT DEFAULT 0 /* (timestamp) */, 
	`next_attempt` BIGINT DEFAULT 0 /* (timestamp) */, 
	`serialized` TEXT /* serialized callback + data */
);

/* generalized log table */
CREATE TABLE logs (
	`log_id` CHAR(31) NOT NULL PRIMARY KEY,
	`type` CHAR(31), 
	`created` BIGINT DEFAULT 0 /* (timestamp) */, 
	`tag` CHAR(31) /* can be used to store statuses */,
	`message` VARCHAR(255)
);
CREATE INDEX `logs_tag_index` ON `logs`(`tag`);