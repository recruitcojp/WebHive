CREATE SEQUENCE users_id_seq;
CREATE SEQUENCE hiveqls_id_seq;
CREATE SEQUENCE queryhists_id_seq;
CREATE SEQUENCE runhists_id_seq;

CREATE TABLE cake_sessions (
  id varchar(255) NOT NULL default '',
  data text,
  expires int default NULL,
  PRIMARY KEY  (id)
);

CREATE TABLE users (
id              int default nextval('users_id_seq'),
username        varchar(50),
password        varchar(50),
authority               int,
hive_host       varchar(50),
hive_port       int,
hive_database   varchar(100),
created         timestamp default null,
modified        timestamp default null,
PRIMARY KEY (id)
);

CREATE TABLE hiveqls (
id              int default nextval('hiveqls_id_seq'),
username        varchar(50),
title           varchar(256),
query           varchar(2048),
created         timestamp default null,
modified        timestamp default null,
PRIMARY KEY (id)
);

CREATE TABLE queryhists (
id               int default nextval('queryhists_id_seq'),
hiveqls_id		 int,
username         varchar(50),
title            varchar(256),
query            varchar(2048),
created          timestamp default null,
modified         timestamp default null,
PRIMARY KEY (id)
);

CREATE TABLE runhists (
id               int default nextval('runhists_id_seq'),
username         varchar(50),
hive_host        varchar(50),
hive_port        int,
hive_database    varchar(100),
query            varchar(2048),
rid		 varchar(30),
rsts		 int,
findate		 timestamp,
created          timestamp default null,
modified         timestamp default null,
PRIMARY KEY (id)
);

