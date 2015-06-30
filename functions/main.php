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
	/* 	if (dbclose($db)) {
	  return FALSE;
	  } */
	$config["init"] = FALSE;
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
		return 1;
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
	if ($room->state == "Lobby") {
		insertLobby(array(
			'id' => $room->attributes()['id'],
			'map' => $room->map,
			'db' => $db
		));
	} else {
		$res = searchExisted(array(
			'name' => $name,
			'id' => $room->attributes()['id'],
			'map' => $room->map,
			'time' => $room->gametime,
			'db' => $db
		));
		$row = $res->fetchArray(SQLITE3_NUM);
		if (count($row) == 2) {
			updateExisted(array(
				'gametime' => $room->gametime,
				'curtime' => $curtime,
				'id' => $row[0],
				'db' => $db
			));
		} else {
			insertNew(array(
				'name' => $name,
				'id' => $room->attributes()['id'],
				'count' => $room->roomplayercount,
				'curtime' => $curtime,
				'gametime' => $room->gametime,
				'map' => $room->map,
				'db' => $db
			));
		}
	}
}

function insertLobby(array $req) {
	$insert = "INSERT INTO lobby (count,map) VALUES (:roomid,:map);";
	$stmt = $req['db']->prepare($insert);
	$stmt->bindValue(":roomid", $req['id'], SQLITE3_INTEGER);
	$stmt->bindValue(":map", $req['map'], SQLITE3_TEXT);
	$stmt->execute();
}

function searchExisted(array $req) {
	$select = "SELECT id,state FROM games WHERE "
			. "state=0 AND servername=:name AND roomid=:roomid "
			. "AND map=:map AND gametime<=:gametime;";
	$stmt = $req['db']->prepare($select);
	$stmt->bindValue(":name", $req['name'], SQLITE3_TEXT);
	$stmt->bindValue(":roomid", $req['id'], SQLITE3_INTEGER);
	$stmt->bindValue(":map", $req['map'], SQLITE3_TEXT);
	$stmt->bindValue(":gametime", $req['time'], SQLITE3_TEXT);
	$res = $stmt->execute();
	return $res;
}

function insertNew(array $req) {
	$insert = "INSERT INTO games "
			. "(servername,roomid,count,starttime,gametime,updatetime,map) "
			. "VALUES (:name, :roomid, :count, "
			. ":starttime, :gametime, :updatetime, :map);";
	$stmt = $req['db']->prepare($insert);
	$stmt->bindValue(":name", $req['name'], SQLITE3_TEXT);
	$stmt->bindValue(":roomid", $req['id'], SQLITE3_INTEGER);
	$stmt->bindValue(":count", $req['count'], SQLITE3_INTEGER);
	$stmt->bindValue(":starttime", $req['curtime'], SQLITE3_INTEGER);
	$stmt->bindValue(":gametime", $req['gametime'], SQLITE3_TEXT);
	$stmt->bindValue(":updatetime", $req['curtime'], SQLITE3_INTEGER);
	$stmt->bindValue(":map", $req['map'], SQLITE3_TEXT);
	$stmt->execute();
}

function updateExisted(array $req) {
	$update = "UPDATE games SET "
			. "gametime=:gametime, updatetime=:updatetime WHERE id=:id;";
	$stmt = $req['db']->prepare($update);
	$stmt->bindValue(":gametime", $req['gametime'], SQLITE3_TEXT);
	$stmt->bindValue(":updatetime", $req['curtime'], SQLITE3_INTEGER);
	$stmt->bindValue(":id", $req['id'], SQLITE3_INTEGER);
	$stmt->execute();
}

function closeRooms($curtime, $db) {
	$req = "UPDATE games SET state=1 WHERE updatetime<" . $curtime . " AND state=0;";
	$db->exec($req);
}

function dbopen($dbname) {
	$db = new SQLite3($dbname);
	if (!$db) {
		echo $db->lastErrorMsg();
		return 0;
	} else {
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
