<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1" /> 
<title>Raspberry Pi Enviro - weather data</title>
    <link href="css/print.css" rel="stylesheet" type="text/css" media="print" />
    <link href="css/screen.css" rel="stylesheet" type="text/css" media="screen, projection" />
    <!--[if lte IE 6]><link rel="stylesheet" href="css/ielte6.css" type="text/css" media="screen" /><![endif]-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
</head>
<body>
<div style="max-width: 100%">
<?php
// Set path to directory where user installed myPiWeather
$myPiWeatherDir = "/home/pi/myPiWeather";

// set location of weather logging database
$dbFile = "$myPiWeatherDir/myPiWeather.db";

function SQLite3_connect_try($filename, $mode, $trytoreconnect)
{
	try
	{
		// connect to database
		return new SQLite3($filename, $mode);
	}
	catch (Exception $exception)
	{
		// sqlite3 throws an exception when it is unable to connect, try to reconnect after 3 seconds
		if($trytoreconnect)
		{
			sleep(3);
			return SQLite3_connect_try($filename, $mode, false);
		}
		else
		{
			// If we should not try again (or are already trying again!), we return the exception string
			// so the user gets it on the dashboard
			return $filename.": ".$exception->getMessage();
		}
	}
}

function SQLite3_connect($filename, $mode=SQLITE3_OPEN_READONLY)
{
	if(strlen($filename) > 0)
	{
		$db = SQLite3_connect_try($filename, $mode, true);
	}
	else
	{
		die("No database available");
	}
	if(is_string($db))
	{
		die("Error connecting to database\n".$db);
	}

	// Add busy timeout so methods don't fail immediately when, e.g., app is currently reading from the DB
	$db->busyTimeout(5000);
	return $db;
}

function linear_regression($xydata)
{
	// linear regression of data x,y pairs
	$sx = (float) 0.0;
	$sy = (float) 0.0;
	$stt = (float) 0.0;
	$sts = (float) 0.0;
	$n = sizeof($xydata);
	for($i = 0; $i < $n; $i++)
	{
		$sx += $xydata[$i][0];
		$sy += $xydata[$i][1];
	}
	for ($i = 0; $i < $n; $i++)
	{
		$t = $xydata[$i][0] - $sx/$n;
		$stt += $t*$t;
		$sts += $t*$xydata[$i][1];
	}
	// avoid trying to divide by zero
	if ($stt != 0)
	{
    	$slope = $sts/$stt;
    } else {
        $slope = 0;
    }
    // if we wanted to know the intercept we could get it from: $intercept = ($sy - $sx*$slope)/$n;
	return($slope);
}

$db = SQLite3_connect($dbFile);
// set up database queries for latest sample and to calculate rate of change of pressure over last 3 hours 
$res = $db->query("SELECT * FROM dataSamples ORDER BY id DESC LIMIT 1");
$res2 = $db->query("select timestamp, BME280_pres from dataSamples ORDER BY timestamp DESC LIMIT 18"); // if sampling every 10 mins, 18 samples = 180 min = 3 hours

// calculate rate of change of pressure over last 3 hours 
$i = 0;
while ($row = $res2->fetchArray()) {
    $xydata[$i] = $row;
    $i++;
}
$slope = linear_regression($xydata)*1080;

// retrieve last sample data and format some html
while ($row = $res->fetchArray()) {
    if ($row['DS18B20_ext'] === NULL)
    {
        // we don't seem to have any DS18B20_ext data - perhaps there is no probe connected
        $format = "<p align=\"center\">\nTemperature: %01.1f°C<br>\nHumidity: %01.1f&percnt;<br>\nDate / time: ";
        $info = sprintf($format, $row['BME280_temp'], $row['BME280_humi']);
    } else {
        // we have DS18B20_ext data - include the DS18B20 temperature in the html output
        $format = "<p align=\"center\">DS18B20 temperature: %01.1f°C<br>\nBME280 temperature: %01.1f°C<br>\nHumidity: %01.1f&percnt;<br>\nDate / time: ";
        $info = sprintf($format, ($row['DS18B20_ext'] / 1000), $row['BME280_temp'], $row['BME280_humi']);
    }
    $info .= date('r',$row['timestamp'])."</p>\n";
    $pressure = $row['BME280_pres'];
}

// print html table containing barometer dial and latest sample data
print "<table border=0 cellpadding=0><tr><td><img alt=\"Barometer\" src=\"imagedial.php?mb=".$pressure."&amp;slope=".$slope."\"></td></tr>\n<tr><td>".$info."</td></tr></table>";

?>
</div>
</body>
</html>
