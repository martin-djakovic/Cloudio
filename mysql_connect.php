<?php

require_once "Mysql.php";
require_once "mysql_credentials.php";

$db = new Mysql(DB_HOSTNAME, DB_USER, DB_PASSWORD, DATABASE);