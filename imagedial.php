<?php
// Set path to directory where user installed myPiWeather
$myPiWeatherDir = "/home/pi/myPiWeather";

// Set fonts and images to be used
$ttfont = "$myPiWeatherDir/Roboto-Medium.ttf";
$pointerGif = "$myPiWeatherDir/pointer.gif";
$facePng = "$myPiWeatherDir/face_hPa.png";

// Get pressure and rate of change (slope) from GET input vars
$mb = floatval(trim($_GET['mb']));
$slope = floatval(trim($_GET['slope']));

// Calculate pointer angle from pressure
$angle = 1.771798287*(1049.7809-$mb);

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

// Load pointer graphic
$source = imagecreatefromgif($pointerGif) or die("Couldn't load and create pointer graphic from gif file!  Is $pointerGif a valid, readable gif file?");
$sw = imagesx($source);
$sh = imagesy($source);

//  Rotate pointer to required angle
$rotate = imagerotate($source, $angle, 128);
imagedestroy($source);
$rw = imagesx($rotate);
$rh = imagesy($rotate);
$crop = imagecrop($rotate, array(
    'x' => $rw * (1 - $sw / $rw) * 0.5,
    'y' => $rh * (1 - $sh / $rh) * 0.5,
    'width' => $sw,
    'height'=> $sh
));
imagedestroy($rotate);
$white = imagecolorallocate($crop, 255, 255, 255);
imagecolortransparent($crop, $white);
imagefilter($crop, IMG_FILTER_NEGATE);
imagefilter($crop, IMG_FILTER_BRIGHTNESS, 32);

// Load face (600px x 600px) and superimpose pointer
$face = imagecreatefrompng($facePng) or die("Couldn't load and create face graphic from png file! Is $facePng a valid, readable png file?");
$merged = imagecopymerge($face,$crop,0,0,0,0,600,600,100);
imagedestroy($crop);

// Trim 6px off borders of image - face will then be 588px x 588px
$image = imagecrop($face,['x' => 6,'y' => 6,'width' => 588,'height' => 588]);
imagedestroy($face);

// Load font and add text describing rate of change of pressure
$fontsize = 20;
$black = imagecolorallocate($image, 30, 30, 36);
$tb = imagettfbbox($fontsize, 0, $ttfont, $change) or die("Couldn't calculate dimensions of text box! Is $ttfont a valid, readable truetype font file?");
$textXpos = ceil((588 - $tb[2]) / 2);
$textYpos = 578;
imagettftext($image, $fontsize, 0, $textXpos, $textYpos, $black, $ttfont, $change);

// Return image to browser
header( "Content-type: image/png" );
imagepng($image);
imagedestroy($image);

?>
