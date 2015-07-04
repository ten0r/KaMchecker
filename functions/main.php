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
	global $config;
	$dbname = $config["dbname"];
	if (file_exists($dbname)) {
		if (!archieveOld($dbname)) {
			return FALSE;
		}
	}
	if (!$db = dbopen($dbname)) {
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

function getXML() {
	global $config;
	$files = $config["files"];
	foreach ($files as $filename => $file) {
		$filehandle = fopen($file, "r");
		if (!$filehandle) {
			continue;
		}
		$datas[$filename] = '';
		while (!feof($filehandle)) {
			$datas[$filename] .= fread($filehandle, 8192);
		}
	}
	return $datas;
}

function xmlToDB($datas, $dbname) {
	$curtime = time();
	echo date('d-m-Y_H:i:s', $curtime) . " $curtime\n";
	if (!$db = dbopen($dbname)) {
		return FALSE;
	}

	$db->exec("DELETE FROM lobby;");
	$db->exec("DELETE FROM sqlite_sequence WHERE name = 'lobby';");

	foreach ($datas as $name => $data) {
		$xml = simplexml_load_string($data);
		foreach ($xml->room as $room) {
			updateRooms($name, $curtime, $room, $db);
		}
	}
	closeRooms($curtime, $db);
	if (!dbclose($db)) {
		return FALSE;
	}
	return TRUE;
}

function updateRooms($name, $curtime, $room, $db) {
	//print_r($room->attributes()['id']);
	//print_r($room->players);
	//print_r($room);
	if ($room->state == "Lobby") {
		insertLobby($db, array($room->attributes()['id'], $room->map));
	} else {
		$res = searchExistedGame($db, array($name, $room->attributes()['id'],
			$room->map, $room->gametime));
		$row = $res->fetchArray(SQLITE3_NUM);
		if (count($row) === 1 and $row !== FALSE) {
			updateExisted($db, array($room->gametime, $curtime, $row[0]));
			$id = $row[0];
		} else {
			insertNew($db, array($name, $room->attributes()['id'],
				$room->roomplayercount, $curtime, $room->gametime, $curtime,
				$room->map));
			$id = $db->lastInsertRowID();
		}
		updatePlayers($db, $room->players, $id);
	}
}

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

function typeToSqlite($type) {
	switch ($type) {
		case "i": return SQLITE3_INTEGER;
		case "d": return SQLITE3_FLOAT;
		case "s": return SQLITE3_TEXT;
		case "b": return SQLITE3_BLOB;
		case "n": return SQLITE3_NULL;
	}
}

function insertLobby($db, $params) {
	$insert = "INSERT INTO lobby (count,map) VALUES (?,?);";
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "is");
	$stmt->execute();
}

function searchExistedGame($db, $params) {
	$select = "SELECT id FROM games WHERE state=0 AND servername=?
		AND roomid=? AND map=? AND gametime<=?;";
	$stmt = $db->prepare($select);
	stmtBind($stmt, $params, "siss");
	$res = $stmt->execute();
	return $res;
}

function insertNew($db, $params) {
	$insert = "INSERT INTO games
		(servername,roomid,count,starttime,gametime,updatetime,map)
		VALUES (?, ?, ?, ?, ?, ?, ?);";
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "siiisis");
	$stmt->execute();
}

function updateExisted($db, $params) {
	$update = "UPDATE games SET gametime=?, updatetime=? WHERE id=?;";
	$stmt = $db->prepare($update);
	stmtBind($stmt, $params, "sii");
	$stmt->execute();
}

function updatePlayers($db, $players, $roomid) {
	foreach ($players->player as $player) {
		if (!$playerID = searchExistedPlayerName($db, $player)) {
			$playerID = insertNewPlayer($db, $player);
		}
		echo "$player " . $player->attributes()['type'] . " $playerID\n";
		$params = array($playerID, $roomid, $player->attributes()['connected'],
			ltrim($player->attributes()['color'],"#"));
		linkUsersToGames($db, $params);
	}
}

function searchExistedPlayerName($db, $player) {
	$select = "SELECT id FROM users WHERE name=?";
	$stmt = $db->prepare($select);
	if ($player->attributes()['type'] == "AI Player") {
		$name = "AI Player";
	} else {
		$name = $player;
	}
	stmtBind($stmt, array($name), "s");
	$res = $stmt->execute();
	$row = $res->fetchArray(SQLITE3_NUM);
	if (count($row) !== 1 or $row === FALSE) {
		return FALSE;
	}
	return $row[0];
}

function insertNewPlayer($db, $player) {
	$insert = "INSERT INTO users (name) VALUES (?);";
	$stmt = $db->prepare($insert);
	if ($player->attributes()['type'] == "AI Player") {
		$name = "AI Player";
	} else {
		$name = $player;
	}
	stmtBind($stmt, array($name), "s");
	$stmt->execute();
	return $db->lastInsertRowID();
}

function linkUsersToGames($db, $params) {
	$insert = "INSERT OR REPLACE INTO usersInGames
		(iduser, idgame, connected, color) VALUES (?,?,?,?)";
	$stmt = $db->prepare($insert);
	stmtBind($stmt, $params, "iiis");
	$stmt->execute();
}

function closeRooms($curtime, $db) {
	$update = "UPDATE games SET state=1 WHERE updatetime<$curtime AND state=0;";
	$db->exec($update);
}

function dbopen($dbname) {
	$db = new SQLite3($dbname);
	if (!$db) {
		echo $db->lastErrorMsg();
		return FALSE;
	} else {
		$db->exec('PRAGMA foreign_keys = ON;');
		echo "Opened database successfully\n";
		return $db;
	}
}

function dbclose($db) {
	if (!$db->close()) {
		echo "Close error\n";
		return FALSE;
	}
	echo "Closed successfully\n";
	return TRUE;
}
