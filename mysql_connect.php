<?php

require_once "Mysql.php";
require_once "mysql_credentials.php";

const DB_HOSTNAME = "host.docker.internal";
const DATABASE = "cloudio";

$db = new Mysql(DB_HOSTNAME, DB_USER, DB_PASSWORD, DATABASE) or die("Connection failed. Contact webpage administrator");