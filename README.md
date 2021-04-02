# myPiWeather
Record BME280 and DS18B20 sensor information locally and (optionally) upload to thingspeak. View current data with local php web page.

This is a python script for the Raspberry Pi with BME280 temperature, pressure and humidity sensor. The script also supports an additional DS18B20 one-wire temperature sensor if you have one. Weather data is logged to a sqlite database.

An example PHP script is included which renders a web page with dynamically generated graphic image of an analogue barometer face with pointer which indicates the air pressure and whether the current trend is rising, falling, or steady. Support for uploading data to thingspeak channels is also included.

Requirements
------------

You will need a Raspberry Pi computer with BME280 ambient sensor connected and working. This script was written for the Pimoroni BME280 sensor attached to a Pi Zero-W but should work with most other makes. You will need to have sqlite3 installed on your Pi.
You will need to have the following python modules installed:
* pimoroni-bme280
* thingspeak

If you want to use the supplied php web page you will need to have a php-enabled webserver up and running. For example, lighttpd. You will need the php sqlite3 and php gd packages installed in order for the web script to read weather data from the logger database and to create dynamic images on the fly. You will also have to download the google 'Roboto' font from https://fonts.google.com/specimen/Roboto if you don't already have it.

Installation
------------

Once you've cloned the git files to your computer you will need to configure a few settings. 
If you're considering logging data into thingspeak then now's the time to create your account and thingspeak channel.

Open 'myPiWeather_sampler.py' with a text editor. Near the top of the file you will find some comments indicating that you configure the following variables:
* **my_database** - set the path to the sqlite3 database where you want your data logged to. You should keep the database in the same directory you've cloned the myPiWeather files into with git. Provided you've specified a location which the script has permission to write to, a new database will be created for you if one doesn't already exist.
* **probe** - if you have an additional DS18B20 one-wire temperature sensor connected and you want to use it, you must set its address. If your DS18B20 is connected and functioning, at the command prompt run "ls /sys/bus/w1/devices/" and you should see directories named after the ID of every probe connected to your system. Each DS18B20 has a unique ID consisting of 15 characters. If you have no DS18B20 or don't wish to use one, just set the probe variable to "None".
* **channel_id** - you don't want to use thingspeak or don't have a channel, set channel_id = 0. Otherwise, for information on thingspeak see https://thingspeak.com/ where you can set up a free account and create or configure a channel. To discover your Channel ID and Write API Key, log in to https://thingspeak.com/, go to https://thingspeak.com/channels, click 'API Keys' under the channel that you want to configure. You will see your Channel ID number there as well as the Write API Key. Make sure you don't put any 'quotes' around the channel_id variable - it is not a string, it is numerical. The write_api_key variable, on the other hand, is a string and does need to be quoted. 
* **write_api_key** - see above notes. If you're not using thingspeak you don't need to change this and can ignore it.
* **bme_i2c_addr** - unlikely you will need to change this but if your BME280 sensor has a different address to the default 0x76, then you will need to set that here. It will be either 0x76 or 0x77. Make sure you don't put any 'quotes' around this variable - it is not a string, it is numerical. It is possible to attach two separate BME280 sensors to one Pi, in doing so they must be configured to use different addresses. This is typically done by taking a knife to the sensor board and breaking a link on it.

To make your myPiWeather_sampler.py script executable, you will need to set the correct permissions. You can do so by opening a command prompt, changing directory to the myPiWeather directory where the script is and running "chmod 755 myPiWeather_sampler.py".

You'll probably want to take regular samples every few minutes. The easiest way to do this is probably via a crontab entry. If you run "crontab -e" you can add a line like the following to sample your sensors every 10 minutes:

*/10 * * * * /home/pi/myPiWeather/myPiWeather_sampler.py

Make sure the path you enter into crontab corresponds with wherever you have put your myPiWeather_sampler.py script, and that the script has been made executable. 

If you're wanting to use the supplied local web page, open 'weather.php' with a text editor. The variables you will need to configure are near the top. Look for the following lines:
* **$myPiWeatherDir** = "/home/pi/myPiWeather"; - set this variable to the location you've cloned the myPiWeather files into. This is where the script will look for the database of logged data. If you've named the database anything other than myPiWeather.db or want to store it anywhere else, you will also need to edit the $dbFile variable accordingly.

The web page calls another php script to draw the dynamic images of the barometer with pointer. Open 'imagedial.php' with a text editor. You will need to set the **$myPiWeatherDir** variable here too, exactly the same as in 'weather.php'.

The web page requires that you download the 'Roboto' google font family. You can get it from https://fonts.google.com/specimen/Roboto. Once you've downloaded the zip file, extract the 'Roboto-Medium.ttf' file to your myPiWeather directory, alongside all the other scripts and files.

To install the web page into your web server tree, copy the two files 'weather.php' and 'imagedial.php' to your preferred location (for the default lighttpd setup, this will be under /var/www/html - if you don't want them in the server root directory put them in a subdirectory of your choosing, e.g. /var/www/html/ambient). You can rename 'weather.php' to anything you like (e.g. 'index.php') so long as the 'imagedial.php' file is in the same location.

Please note that while the web page will work quite happily on a home network, you probably don't want random internet traffic coming in to use it, processing graphics and generating images could be a bit of a drain on your resources if requests come in at a high rate.

