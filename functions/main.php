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
 * Downloads xml files
 *
 * @global array $_config
 * @return array
 */
function getXML() {
	global $_config;
	$files = $_config["files"];
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

/**
 * Puts data from XML to database
 *
 * @param array $datas
 * @return boolean
 */
function xmlToDB($datas) {
	$curtime = time();
	echo date('d-m-Y_H:i:s', $curtime) . " $curtime\n";
	if (!$db = dbopen()) {
		return FALSE;
	}
	clearLobbies($db);
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

/**
 * Controls information about room current status
 *
 * @param string $name
 * @param int $curtime
 * @param SimpleXMLElement $room
 * @param SQLite3 $db
 */
function updateRooms($name, $curtime, $room, $db) {
	if ($room->state == "Lobby") {
		insertLobby($db, [$room->attributes()['id'], $room->map]);
	} else {
		$id = searchExistingGame($db, [$name, $room->attributes()['id'],
			$room->map, $room->gametime]);
		if ($id) {
			updateExisting($db, [$room->gametime, $curtime, $id]);
		} else {
			insertNew($db, [$name, $room->attributes()['id'],
				$room->roomplayercount, $curtime, $room->gametime, $curtime,
				$room->map]);
			$id = lastInsertId($db);
		}
		updatePlayers($db, $room->players, $id);
	}
}

/**
 * Controls information about players in the room
 *
 * @param SQLite3 $db
 * @param SimpleXMLElement $players
 * @param int $roomid
 */
function updatePlayers($db, $players, $roomid) {
	foreach ($players->player as $player) {
		if (!$playerID = searchExistingPlayerName($db, $player)) {
			$playerID = insertNewPlayer($db, $player);
		}
		$params = [$playerID, $roomid, $player->attributes()['connected'],
			ltrim($player->attributes()['color'], "#")];
		linkUsersToGames($db, $params);
	}
}
