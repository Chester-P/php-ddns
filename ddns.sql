/**
 * This is the table structure for the ddns scirpts
 *
 * version 1.1.1
 */


CREATE TABLE `RR` (
  `SN` int(20) NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `FQDN` varchar(64) NOT NULL DEFAULT '',
  `TTL` int(5) NOT NULL DEFAULT '60',
  `type` varchar(10) NOT NULL DEFAULT '',
  `value` varchar(64) NOT NULL DEFAULT '127.0.0.1',
  `create_time` datetime,
  `SOA_Serial` int(10) NOT NULL

) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `RR_LOG` (
  `SN` int(20) NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `FQDN` varchar(64) NOT NULL DEFAULT '',
  `TTL` int(5) NOT NULL DEFAULT '60',
  `type` varchar(10) NOT NULL DEFAULT '',
  `value` varchar(64) NOT NULL DEFAULT '',
  `create_time` datetime,
  `SOA_Serial` int(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `user` (
  `SN` int(20) NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `email` varchar(64) NOT NULL DEFAULT '',
  `memo` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `RR`
  ADD PRIMARY KEY (`SN`),
  ADD KEY `username` (`username`),
  ADD KEY `FQDN` (`FQDN`);

ALTER TABLE `RR_LOG`
  ADD PRIMARY KEY (`SN`),
  ADD KEY `username` (`username`),
  ADD KEY `FQDN` (`FQDN`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`SN`),
  ADD KEY `username` (`username`);


ALTER TABLE `RR`
  MODIFY `SN` int(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `RR_LOG`
  MODIFY `SN` int(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user`
  MODIFY `SN` int(20) NOT NULL AUTO_INCREMENT;


/**
 * Add your login credentials here!
 */
#INSERT INTO USER (USERNAME, PASSWD) VALUES('USERNAME', md5('PASSWORD'));
