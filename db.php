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
$conf = "config.php";
include_once($conf);
include_once($config["functions"]);
if (!ini_get('date.timezone')) {
	date_default_timezone_set('GMT');
}
if ($config["init"]) {
	echo "Install\n";
	echo "Creating database\n";
	if (init($config["dbname"])) {
		echo "Terminating\n";
		return 1;
	}
	echo "Disabling install mode\n";
	$conf_contents = file_get_contents($conf);
	$conf_contents = preg_replace("/(\"init\"\s*=>\s*)true/", "$1false", $conf_contents);
	file_put_contents($conf, $conf_contents);
}
$data = getXML($config["files"]);
if (xmlToDB($data, $config["dbname"])) {
	echo "Open failed\n";
	return 1;
}
return 0;
