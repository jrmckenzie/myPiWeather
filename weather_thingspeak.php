<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
<title>Raspberry Pi - weather data</title>
    <link href="weather/css/print.css" rel="stylesheet" type="text/css" media="print" />
    <link href="weather/css/screen.css" rel="stylesheet" type="text/css" media="screen, projection" />
    <!--[if lte IE 6]><link rel="stylesheet" href="weather/css/ielte6.css" type="text/css" media="screen" /><![endif]-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
</head>
<body>
<div style="max-width: 100%">
<?php

// Copyright (C) 2024 James McKenzie jrmknz@yahoo.co.uk

// Set your thingspeak channel ID

$channelID = '1204661';

// Use curl to get the data feed from the thingspeak channel

$url = "https://thingspeak.mathworks.com:443/channels/" . $channelID . "/feed.csv";
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($curl);
curl_close($curl);

// Get array of last 18 thingspeak samples (which should cover 3 hours if samples are every 10 min)

if (!$resp) {
        echo "Error triggered: $errstr ($errno)<br />\n";
} else {
        $lines = explode(PHP_EOL, $resp);
        $array = array();
        foreach ($lines as $line) {
                $array[] = str_getcsv($line);
        }
        $headers = array_pop($array);
        $samples = array();
        for($count = 0; $count < 18; $count++) {
                $samples[] = array_pop($array);
                $samples[$count][0] = strtotime($samples[$count][0]);
        }
}

function describe_pressure($pressure)
{
    // Convert pressure into barometer-type description.
    if($pressure < 970)
	{
		return "Storm";
	}
	elseif($pressure < 990)
	{
		return "Rain";
	}
	elseif($pressure < 1010)
	{
		return "Change";
	}
	elseif($pressure < 1030)
	{
		return "Fair";
	}
	elseif($pressure >= 1030)
	{
		return "Dry";
	}
	return;
}

function linear_regression($xydata)
{
	// Linear regression of data x,y pairs
	$sx = (float) 0.0;
	$sy = (float) 0.0;
	$stt = (float) 0.0;
	$sts = (float) 0.0;
	$n = sizeof($xydata);
	for($i = 0; $i < $n; $i++)
	{
		$sx += $xydata[$i][0];
		$sy += $xydata[$i][3];
	}
	for ($i = 0; $i < $n; $i++)
	{
		$t = $xydata[$i][0] - $sx/$n;
		$stt += $t*$t;
		$sts += $t*$xydata[$i][3];
	}
	$slope = $sts/$stt;
	$intercept = ($sy - $sx*$slope)/$n;
	return($slope);
}

// Calculate rate of change of pressure over last 3 hours
$slope = linear_regression($samples)*1080;
if ($slope > 6) { $change = "rising very rapidly"; }
elseif ($slope >= 3.55) { $change = "rising quickly"; }
elseif ($slope >= 1.55) { $change = "rising"; }
elseif ($slope >= 0.1) { $change = "rising slowly"; }
elseif ($slope >= 0.02) { $change = "rising more slowly"; }
elseif ($slope > -0.02) { $change = "steady"; }
elseif ($slope > -0.1) { $change = "falling more slowly"; }
elseif ($slope > -1.55) { $change = "falling slowly"; }
elseif ($slope > -3.55) { $change = "falling"; }
elseif ($slope >= -6) { $change = "falling quickly"; }
elseif ($slope < -6) { $change = "falling very rapidly"; }

// Retrieve last sample data
$description = describe_pressure($samples[0][3]);
$format = "<p align=\"center\">Outside temperature: %01.1f&deg;C<br>\nInside temperature: %01.1f&deg;C<br>\nPressure: %01.1fmb<br>\nHumidity: %01.1f&percnt;<br>\nSample date / time: ";
$info = sprintf($format, $samples[0][6], $samples[0][2], $samples[0][3], $samples[0][4]);
$info .= date('r',$samples[0][0])."</p>\n";

// Print html table containing barometer dial and latest sample data
print "<table border=0 cellpadding=0><tr><td><img alt=\"".$description."\" src=\"weather/imagedial.php?mb=".$samples[0][3]."&amp;slope=".$slope."\"></td></tr>\n<tr><td>".$info."</td></tr></table>";

?>
</div>
</body>
</html>
