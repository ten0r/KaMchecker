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
 *
 * @global array $_config
 * @return boolean
 */
function init() {
	global $_config;
	$dbname = $_config["dbname"];
	if (file_exists($dbname)) {
		if (!archieveOld($dbname)) {
			return FALSE;
		}
	}
	if (!$db = dbopen()) {
		return FALSE;
	}
	$sql = file_get_contents("init.sql");
	$ret = $db->exec($sql);
	if (!$ret) {
		echo $db->lastErrorMsg();
		return FALSE;
	}
	echo "Table created successfully\n";
	return TRUE;
}

/**
 *
 * @param string $dbname
 * @return boolean
 */
function archieveOld($dbname) {
	echo "Moving old file\n";
	$backupDir = date('d-m-Y_H:i:s', time());
	if (!mkdir($backupDir, 0755)) {
		echo "Backup dir not created\nBreaking\n";
		return FALSE;
	}
	$gzfile = $backupDir . "/" . $dbname . ".gz";
	if (!$fp = gzopen($gzfile, "w9")) {
		echo "Can't compress file\nBreaking\n";
		return FALSE;
	}
	gzwrite($fp, file_get_contents($dbname));
	gzclose($fp);
	if (!unlink($dbname)) {
		echo "Can't remove old database\nBreaking\n";
		return FALSE;
	}
	return TRUE;
}

/**
 *
 * @param SQLite3 $db
 */
function clearLobbies($db) {
	$db->exec("DELETE FROM lobby;");
	$db->exec("DELETE FROM sqlite_sequence WHERE name = 'lobby';");
}

/**
 *
 * @param SQLite3Stmt $stmt
 * @param array $params
 * @param string $outertypes
 * @return boolean
 */
function stmtBind($stmt, $params, $outertypes) {
	$types = strtolower($outertypes);
	if (count($params) !== strlen($types)) {
		echo "Number of parameters differs from number of types\n";
		return FALSE;
	}
	if (preg_match("/[^idsbn]+/", $types)) {
		echo "Unsuported symbols in types string\n";
		echo "$types\n";
		return FALSE;
	}
	for ($i = 0; $i < count($params); $i++) {
		$stmt->bindValue($i + 1, $params[$i], typeToSqlite($types[$i]));
	}
	return TRUE;
}

/**
 *
 * @param string $type
 * @return int
 */
function typeToSqlite($type) {
	switch ($type) {
		case "i": return SQLITE3_INTEGER;
		case "d": return SQLITE3_FLOAT;
		case "s": return SQLITE3_TEXT;
		case "b": return SQLITE3_BLOB;
		case "n": return SQLITE3_NULL;
	}
}

/**
 *
 * @param SQLite3 $db
 * @param array $params
 */
function insertLobby($db, $params) {
	$insert = "INSERT INTO lobby (count,map) VALUES (?,?);";
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "is");
	$stmt->execute();
}

/**
 *
 * @param SQLite3 $db
 * @param array $params
 * @return boolean
 */
function searchExistedGame($db, $params) {
	$select = "SELECT id FROM games WHERE state=0 AND servername=?
		AND roomid=? AND map=? AND gametime<=?;";
	$stmt = $db->prepare($select);
	stmtBind($stmt, $params, "siss");
	$res = $stmt->execute();
	$row = $res->fetchArray(SQLITE3_NUM);
	if (count($row) !== 1 or $row === FALSE) {
		return FALSE;
	}
	return $row[0];
}

/**
 *
 * @param SQLite3 $db
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
 *
 * @param SQLite3 $db
 * @param array $params
 */
function updateExisted($db, $params) {
	$update = "UPDATE games SET gametime=?, updatetime=? WHERE id=?;";
	$stmt = $db->prepare($update);
	stmtBind($stmt, $params, "sii");
	$stmt->execute();
}

/**
 *
 * @param SQLite3 $db
 * @param SimpleXMLElement $player
 * @return boolean
 */
function searchExistedPlayerName($db, $player) {
	$select = "SELECT id FROM users WHERE name=?";
	$stmt = $db->prepare($select);
	if ($player->attributes()['type'] == "AI Player") {
		$name = "AI Player";
	} else {
		$name = $player;
	}
	stmtBind($stmt, [$name], "s");
	$res = $stmt->execute();
	$row = $res->fetchArray(SQLITE3_NUM);
	if (count($row) !== 1 or $row === FALSE) {
		return FALSE;
	}
	return $row[0];
}

/**
 *
 * @param SQLite3 $db
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
	return $db->lastInsertRowID();
}

/**
 *
 * @param SQLite3 $db
 * @param array $params
 */
function linkUsersToGames($db, $params) {
	$insert = "INSERT OR REPLACE INTO usersInGames
		(iduser, idgame, connected, color) VALUES (?,?,?,?)";
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "iiis");
	$stmt->execute();
}

/**
 *
 * @param int $curtime
 * @param SQLite3 $db
 */
function closeRooms($curtime, $db) {
	$update = "UPDATE games SET state=1 WHERE updatetime<$curtime AND state=0;";
	$db->exec($update);
}

/**
 *
 * @global array $_config
 * @return \SQLite3|boolean
 */
function dbopen() {
	global $_config;
	$dbname = $_config["dbname"];
	$db = new SQLite3($dbname);
	if (!$db) {
		echo $db->lastErrorMsg();
		return FALSE;
	}
	$db->exec('PRAGMA foreign_keys = ON;PRAGMA busy_timeout=100;');
	echo "Opened database successfully\n";
	return $db;
}

/**
 *
 * @param SQLite3 $db
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
