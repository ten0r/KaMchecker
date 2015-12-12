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
	$sql = file_get_contents("sql/mysql.sql");
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
	$host = $_config["host"];
	$port = $_config["port"];
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
 * Fills lobby table
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
 * Binds stmt values to mysqli stmt
 *
 * @param mysqli_stmt $stmt
 * @param array $params
 * @param string $types
 */
function stmtBind($stmt, $params, $types) {
	array_unshift($params, $types);
	call_user_func_array(array($stmt, 'bind_param'), refValues($params));
}

/**
 * Convert array of to array of references to the elements of the initial array
 * 
 * @param array $arr
 * @return array
 */
function refValues($arr) {
	$refs = array();
	foreach (array_keys($arr) as $key) {
		$refs[$key] = &$arr[$key];
	}
	return $refs;
}

/**
 * Searches for existing games
 * 
 * @param mysqli $db
 * @param array $params
 * @return int
 */
function searchExistingGame($db, $params) {
	$select = "SELECT id FROM games WHERE state=0 AND servername=?
		AND roomid=? AND map=? AND gametime<=?;";
	$stmt = $db->prepare($select);
	stmtBind($stmt, $params, "siss");
	$stmt->execute();
	$res = $stmt->get_result();
	$row = $res->fetch_array(MYSQLI_NUM);
	if (count($row) !== 1 or $row === FALSE) {
		return FALSE;
	}
	return $row[0];
}

/**
 * Fills games table with new game rooms
 * 
 * @param mysqli $db
 * @param array $params
 */
function insertNew($db, $params) {
	$insert = "INSERT INTO games
		(servername,roomid,count,starttime,gametime,updatetime,map)
		VALUES (?, ?, ?, ?, ?, ?, ?);";
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "siiisis");
	$stmt->execute();
}

/**
 * Returns the auto generated id used in the last query
 * 
 * @param mysqli $db
 * @return int
 */
function lastInsertId($db) {
	return $db->insert_id;
}

/**
 * Updates existing games
 * 
 * @param mysqli $db
 * @param array $params
 */
function updateExisting($db, $params) {
	$update = "UPDATE games SET gametime=?, updatetime=? WHERE id=?;";
	$stmt = $db->prepare($update);
	stmtBind($stmt, $params, "sii");
	$stmt->execute();
}

/**
 * Searches for existing users
 * 
 * @param mysqli $db
 * @param SimpleXMLElement $player
 * @return int
 */
function searchExistingPlayerName($db, $player) {
	$select = "SELECT id FROM users WHERE name=?";
	$stmt = $db->prepare($select);
	if ($player->attributes()['type'] == "AI Player") {
		$name = "AI Player";
	} else {
		$name = $player;
	}
	stmtBind($stmt, [$name], "s");
	$stmt->execute();
	$res = $stmt->get_result();
	$row = $res->fetch_array(MYSQLI_NUM);
	if (count($row) !== 1 or $row === FALSE) {
		return FALSE;
	}
	return $row[0];
}

/**
 * Fills users table with new users
 * 
 * @param mysqli $db
 * @param SimpleXMLElement $player
 * @return int
 */
function insertNewPlayer($db, $player) {
	$insert = "INSERT INTO users (name) VALUES (?);";
	$stmt = $db->prepare($insert);
	if ($player->attributes()['type'] == "AI Player") {
		$name = "AI Player";
	} else {
		$name = $player;
	}
	stmtBind($stmt, [$name], "s");
	$stmt->execute();
	return $db->insert_id;
}

/**
 * Fills userInGames table with data about players in rooms
 * 
 * @param mysqli $db
 * @param array $params
 */
function linkUsersToGames($db, $params) {
	$insert = "INSERT IGNORE INTO usersInGames	
		(iduser, idgame, connected, color) VALUES (?,?,?,?)";
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "iiis");
	$stmt->execute();
}

/**
 * Closes ended games
 * 
 * @param mysqli $db
 * @param int $curtime
 */
function closeRooms($db, $curtime) {
	$update = "UPDATE games SET state=1 WHERE updatetime<$curtime AND state=0;";
	$db->query($update);
}
