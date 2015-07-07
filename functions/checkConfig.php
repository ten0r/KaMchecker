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

if (!isset($config["init"])) {
	$config["init"] = true;
}
if (!isset($config["dbtype"])) {
	$dbtype = readline("Choose database: (1)sqlite, (2)mysql\nDefault sqlite. ");
	if ($dbtype == 2) {
		$config["dbtype"] = "mysql";
	} else {
		$config["dbtype"] = "sqlite";
	}
}

if ($config["dbtype"] == "mysql") {
	if (!isset($config["host"])) {
		$config["host"] = readline("Set database host");
	}
	if (!isset($config["port"])) {
		$config["port"] = readline("Set database port");
	}
	if (!isset($config["user"])) {
		$config["user"] = readline("Set database access user\n");
	}
	if (!isset($config["password"])) {
		$config["password"] = readline("Set database user password\nLeave empty for anonymous access");
	}
}

if (!isset($config["dbname"])) {
	$config["dbname"] = readline("Set database name\n");
}
if (!isset($config["files"])) {
	$config["files"] = array(
		"example" => "http://example.org/status.xml"
	);
}