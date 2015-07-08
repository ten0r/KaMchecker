<?php

/*
 * Copyright (C) 2015 tenor
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function init() {
	if (!$db = dbopen()) {
		return FALSE;
	}
	$sql = file_get_contents("init.sql");
	if (!$db->multi_query($sql)) {
		echo $db->lastErrorMsg();
		return FALSE;
	}
	echo "Table created successfully\n";
	return TRUE;
}

function dbopen() {
	global $_config;
	$host = $_config;
	$port = $_config;
	$database = $_config["dbname"];
	$username = $_config["user"];
	$password = $_config["password"];
	$db = new mysqli($host, $username, $password, $database, $port);
	if ($db->connect_error) {
		echo "Connection error (" . $db->connect_errno . ") " . $db->connect_error;
		return FALSE;
	} else {
		echo "Opened database successfully\n";
		return $db;
	}
}

function dbclose($db) {
	/* @var $db mysqli */
	if (!$db->close()) {
		echo "Close error\n";
		return FALSE;
	}
	echo "Closed successfully\n";
	return TRUE;
}

function clearLobbies($db) {
	/* @var $db mysqli */
	$db->query("DELETE FROM lobby;");
}
