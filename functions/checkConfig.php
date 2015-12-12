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

if (!isset($_config["init"])) {
	$_config["init"] = true;
}
if (!isset($_config["dbtype"])) {
	$dbtype = readline("Choose database: (1)sqlite, (2)mysql\nDefault sqlite. ");
	if ($dbtype == 2) {
		$_config["dbtype"] = "mysql";
	} else {
		$_config["dbtype"] = "sqlite";
	}
}

if ($_config["dbtype"] == "mysql") {
	if (!isset($_config["host"])) {
		$_config["host"] = readline("Set database host: ");
	}
	if (!isset($_config["port"])) {
		$_config["port"] = readline("Set database port: ");
	}
	if (!isset($_config["user"])) {
		$_config["user"] = readline("Set database access user: ");
	}
	if (!isset($_config["password"])) {
		$_config["password"] = readline("Set database user password\nLeave empty for anonymous access: ");
	}
}

if (!isset($_config["dbname"])) {
	$_config["dbname"] = readline("Set database name\n");
}
if (!isset($_config["files"])) {
	$_config["files"] = array(
		"example" => "http://example.org/status.xml"
	);
}