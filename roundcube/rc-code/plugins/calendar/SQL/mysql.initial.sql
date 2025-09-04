CREATE TABLE IF NOT EXISTS `calendar_oauth_refresh_tokens` (
  `provider_id` varchar(255) NOT NULL,
  `client_config_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL,
  `issue_time` int(11) NOT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  UNIQUE KEY `provider_id` (`provider_id`,`client_config_id`,`scope`,`user_id`)
);

CREATE TABLE IF NOT EXISTS `calendar_oauth_states` (
  `provider_id` varchar(255) NOT NULL,
  `client_config_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL,
  `issue_time` int(11) NOT NULL,
  `state` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`,`state`),
  UNIQUE KEY `provider_id` (`provider_id`,`client_config_id`,`scope`,`user_id`)
);

CREATE TABLE IF NOT EXISTS `calendar_oauth_access_tokens` (
  `provider_id` varchar(255) NOT NULL,
  `client_config_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL,
  `issue_time` int(11) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  `token_type` varchar(255) NOT NULL,
  `expires_in` int(11) DEFAULT NULL,
  UNIQUE KEY `provider_id` (`provider_id`,`client_config_id`,`scope`,`user_id`)
);

REPLACE INTO `system` (`name`, `value`) VALUES ('calendar-version', '2016011300');