CREATE TABLE cake_sessions (
  id varchar(255) NOT NULL default '',
  data text,
  expires int(11) default NULL,
  PRIMARY KEY  (id)
);

CREATE TABLE users (
id              integer auto_increment,
username        varchar(50),
password        varchar(50),
authority		int,
hive_host       varchar(50),
hive_port	int,
created         datetime default null,
modified        datetime default null,
PRIMARY KEY (id)
);

CREATE TABLE hiveqls (
id              integer auto_increment,
title           varchar(256),
query           varchar(2048),
created         datetime default null,
modified        datetime default null,
PRIMARY KEY (id)
);

