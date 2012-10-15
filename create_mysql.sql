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
authority       int,
hive_host       varchar(50),
hive_port       int,
hive_database   varchar(100),
created         datetime default null,
modified        datetime default null,
PRIMARY KEY (id)
);

CREATE TABLE hiveqls (
id              integer auto_increment,
username        varchar(50),
title           varchar(256),
query           varchar(60000),
created         datetime default null,
modified        datetime default null,
PRIMARY KEY (id)
);

CREATE TABLE queryhists (
id               integer auto_increment,
hiveqls_id		 integer,
username         varchar(50),
title            varchar(256),
query            varchar(60000),
created          datetime default null,
modified         datetime default null,
PRIMARY KEY (id)
);

CREATE TABLE runhists (
id               integer auto_increment,
username         varchar(50),
hive_host        varchar(50),
hive_port        int,
hive_database    varchar(100),
query            varchar(60000),
rid		 varchar(30),
rsts		 int,
findate		 datetime,
created          datetime default null,
modified         datetime default null,
PRIMARY KEY (id)
);

