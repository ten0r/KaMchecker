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
chdir(dirname(__FILE__));
$config = include_once("config.php");
include_once("functions/main.php");
include_once("functions/checkConfig.php");
if (!ini_get('date.timezone')) {
	date_default_timezone_set('GMT');
}
if ($config["dbtype"]=="mysql") {
	include_once ("functions/mysqlFunctions.php");
}
else {
	include_once ("functions/sqliteFunctions.php");
}
if ($config["init"]) {
	echo "Install\n";
	echo "Creating database\n";
	if (!init()) {
		echo "Terminating\n";
		return 1;
	}
	$config["init"] = FALSE;
	echo "Installation mode disabled\nInstallation finished\n";
}
file_put_contents("config.php", "<?php\n\nreturn " . var_export($config, true) . ";");
$data = getXML($config["files"]);
if (!xmlToDB($data)) {
	echo "Open failed\n";
	return 1;
}
return 0;
