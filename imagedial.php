<?php

// Copyright (C) 2023 James McKenzie jrmknz@yahoo.co.uk
// Set up fonts and images to be used
$facePng = "barometer_600.png";
$textFont = "Rubik-Medium.ttf";
$pointerFont = "barometer.ttf";
$textFontsize = 40;
$pointerFontsize = 910;

// Get pressure and rate of change (slope) from GET input vars
$mb = floatval(trim($_GET['mb']));
$slope = floatval(trim($_GET['slope']));

// Calculate pointer angle from pressure
$angle = 1.8*(1050-$mb);

// Describe rate of change of pressure
if ($slope > 6) { $change = "RISING VERY RAPIDLY"; }
elseif ($slope >= 3.55) { $change = "RISING QUICKLY"; }
elseif ($slope >= 1.55) { $change = "RISING"; }
elseif ($slope >= 0.1) { $change = "RISING SLOWLY"; }
elseif ($slope >= 0.02) { $change = "RISING MORE SLOWLY"; }
elseif ($slope > -0.02) { $change = "STEADY"; }
elseif ($slope > -0.1) { $change = "FALLING MORE SLOWLY"; }
elseif ($slope > -1.55) { $change = "FALLING SLOWLY"; }
elseif ($slope > -3.55) { $change = "FALLING"; }
elseif ($slope >= -6) { $change = "FALLING QUICKLY"; }
elseif ($slope < -6) { $change = "FALLING VERY RAPIDLY"; }

// Load face (recommended to be square and 600px x 600px or 1200px x 1200px)
$face = imagecreatefrompng($facePng) or die("Couldn't load face graphic from png file! Is $facePng a valid, readable png file?");
$faceW = imagesx($face);
$faceH = imagesy($face);
$textFontsize = intval($textFontsize * ($faceW / 1200));
$pointerFontsize = intval($pointerFontsize * ($faceW / 1200));

// Load font and add pointer and text describing rate of change of pressure
$black = imagecolorallocate($face, 0, 0, 0);
$tb = imagettfbbox($textFontsize, 0, $textFont, $change) or die("Couldn't calculate dimensions of text box! Is $textFont a valid, readable truetype font file?");
$textXpos = ceil(($faceW - $trimPx * 2 - $tb[2]) / 2);
$textYpos = $faceH - $trimPx * 2 - 10;
imagettftext($face, $textFontsize, 0, $textXpos, $textYpos, $black, $textFont, $change);
imagettftext($face, $pointerFontsize, $angle, intval($faceW / 2), intval($faceH / 2), $black, $pointerFont, '-') or die("Couldn't draw pointer! Is $pointerFont a valid, readable truetype font file?");

// Dark mode
imagefilter($face, IMG_FILTER_NEGATE);

// Trim $trimPx pixels off borders of image
$trimPx = 0;
if ($trimPx > 0) {
    $face = imagecrop($face,['x' => $trimPx,'y' => $trimPx,'width' => $faceW - $trimPx * 2,'height' => $faceH - $trimPx * 2]);
}

// Return image to browser
header( "Content-type: image/png" );
imagepng($face);
imagedestroy($face);

?>
