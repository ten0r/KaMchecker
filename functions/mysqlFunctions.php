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

/**
 * Creates new database
 *
 * @return boolean
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
	echo "Database created successfully\n";
	return TRUE;
}

/**
 * Opens database access
 *
 * @global array $_config
 * @return boolean|\mysqli
 */
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

/**
 * Closes database access
 *
 * @param mysqli $db
 * @return boolean
 */
function dbclose($db) {
	if (!$db->close()) {
		echo "Close error\n";
		return FALSE;
	}
	echo "Closed successfully\n";
	return TRUE;
}

/**
 * Clears lobbies
 *
 * @param mysqli $db
 */
function clearLobbies($db) {
	/* @var $db mysqli */
	$db->query("DELETE FROM lobby;");
	$db->query("ALTER TABLE lobby AUTO_INCREMENT=0;");
}

/**
 *
 * @param mysqli $db
 * @param array $params
 */
function insertLobby($db, $params) {
	$insert = "INSERT INTO lobby (count,map) VALUES (?,?);";
	/* @var $stmt mysqli_stmt */
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "is");
	$stmt->execute();
}

/**
 *
 * @param mysqli_stmt $stmt
 * @param array $params
 * @param string $types
 */
function stmtBind($stmt, $params, $types) {
	array_unshift($params, $types);
	call_user_func_array(array($stmt,'bind_param'),$params);
}
