<?php
/*
Fetches publication 78 from the IRS and adds the data into the database
Copyright (C) 2016 Vincent Cloutier

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

include_once 'credentials.php';

error_reporting(E_ALL);
set_time_limit(0);

$db = new PDO("mysql:host=" . $mysql["host"] . ';dbname=' . $mysql["db"] . '', $mysql["user"], $mysql["password"], array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
));

// We start by getting a data zip file from the Governement
$dataURL = "https://apps.irs.gov/pub/epostcard/data-download-pub78.zip";

echo "Getting data from the IRS\n";

$zipFileLocation = tempnam("/tmp", "foundations_");
echo "Writing to " . $zipFileLocation . " \n";

// This is a hack because the website has a robots.txt that does not allow download and there is no PHP workaround.
exec("wget -e robots=off --quiet -O " . $zipFileLocation . " " . $dataURL);

echo "Got " . fileSizeConvert(filesize($zipFileLocation)) . " of data \n";

$zip = new ZipArchive;

if ($zip->open($zipFileLocation) === TRUE) {
	$dataLocation = "/tmp/"; 
	$zip->extractTo($dataLocation, array ('data-download-pub78.txt'));
	$zip->close();

	echo "Unzipped data successfully \n";
} else {
	die("Data seems to be corrupted \n");
}

$rawData = file_get_contents("/tmp/data-download-pub78.txt");

$rows = explode(PHP_EOL, $rawData);
array_shift($rows);
array_shift($rows);

echo "There is " . sizeof($rows) . " rows of data \n";

// This is a sanity check. We want to know if the structure has changed. 
// If it is the case, the script will stop so that it does not corrupt the database. 
if (sizeof(explode("|", $rows[0])) != 6) {
	die("The document structure has changed. Please update the script.\n");
}
echo "\n \n";


$i = 0;
foreach ($rows AS $row) {
	$e = explode("|", $row);
	if ($e[4] != "United States") {
		continue;
	}

	$add = $db->prepare("INSERT INTO foundations (gov_id, name, address, legal_type, country) VAlUES "
	. "(:gov_id, :name, :address, :legal_type, :country) "
		. "ON DUPLICATE KEY UPDATE "
			. "name = VALUES (name), address = VALUES (address), legal_type = VALUES (legal_type) ");

	$tmp[6] = "US_" . $e[0];
	$add->bindParam(":gov_id", $tmp[6]);
	$add->bindParam(":name", $e[1]);
	$tmp[2] = "US_" . $e[5];
	$add->bindParam(":legal_type", $tmp[2]);
	$tmp[4] = $e[2] . " " . $e[3] . " " . $e[4];
	$add->bindParam(":address", $tmp[4]);
	$tmp[5] = "US";
	$add->bindParam(":country", $tmp[5]);

	$add->execute();

	$i++;
	if ($i % 1000 == 0) {
		echo $i . " rows have been proccessed. \n";
	}
}


echo "Done! \n \n ";

// Helper functions
function fileSizeConvert($bytes)
{
    $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4)
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3)
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2)
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1
            ),
        );

    foreach($arBytes as $arItem)
    {
        if($bytes >= $arItem["VALUE"])
        {
            $result = $bytes / $arItem["VALUE"];
            $result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
            break;
        }
    }
    return $result;
}

