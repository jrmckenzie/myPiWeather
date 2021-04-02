#!/usr/bin/python

import logging
import os
import sqlite3

# This script requires the thingspeak module, which you can get via the instructions at
# https://pypi.org/project/thingspeak/
# For example, install it with "pip install thingspeak"
import thingspeak

# This script requires the Pimoroni bme280 module from https://github.com/pimoroni/bme280-python.git
# Make sure you have this installed. (It may work with other bme280 modules but is not tested.)
from bme280 import BME280

try:
    from smbus2 import SMBus
except ImportError:
    from smbus import SMBus

# This is the beginning of the user configuration section. Customise to suit your setup.
# Configure the path to your weather data sampling database. The directory will need to be writable by this
# script. If the database doesn't already exist, the script will try to create it automatically.
my_database = "/home/pi/myPiWeather/myPiWeather.db"

# Configure an DS18B20 probe ID - if you don't have any DS18B20 temperature probes please set probe = "None"
# Each DS18B20 has a unique ID consisting of 15 characters and looks something like "28-0114559c70aa"
# If your DS18B20 is connected and functioning, at the command prompt run "ls /sys/bus/w1/devices/" and you
# should see directories named after the ID of every probe connected to your system.
probe = "28-0114559c70aa"

# Set up Thingspeak channel
# For information on thingspeak see https://thingspeak.com/ where you can set up a free account and create
# or configure a channel.
# To discover your Write API Key, log in to https://thingspeak.com/, go to https://thingspeak.com/channels,
# click 'API Keys' under the channel that you want to configure. You will see your channel id number there too.
#  If you don't want to use Thingspeak or don't have a channel, set channel_id = 0
channel_id = 0
write_api_key  = 'insert_your_key_here'

# That is the end of the user configuration section.

# Provide some basic information as the script initialises.
logging.basicConfig(
    format='%(asctime)s.%(msecs)03d %(levelname)-8s %(message)s',
    level=logging.INFO,
    datefmt='%Y-%m-%d %H:%M:%S')

# Test to see if the database exists. If not, try to create it now.
if not os.path.exists(my_database):
    db_create_sql = 'CREATE TABLE dataSamples (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ' \
                    'timestamp DATETIME NOT NULL, DS18B20_ext INTEGER, BME280_temp REAL NOT NULL, ' \
                    'BME280_pres REAL NOT NULL, BME280_humi REAL NOT NULL);'
    try:
        dbCreateConn = sqlite3.connect(my_database)
        logging.info("Database " + my_database + " created.")
        cursor = dbCreateConn.cursor()
        cursor.execute(db_create_sql)
    except sqlite3.Error as e:
        logging.error(e)
    finally:
        dbCreateConn.commit()
        dbCreateConn.close()

if channel_id != 0:
    channel = thingspeak.Channel(id=channel_id, api_key=write_api_key)

bus = SMBus(1)
bme280 = BME280(i2c_dev=bus)

# Set the BME280 sensors to "forced" mode which is supposed to be best for long sampling intervals
bme280.setup(mode="forced", temperature_oversampling=1, pressure_oversampling=1, humidity_oversampling=1)

logging.basicConfig(level=logging.ERROR)

logging.info("""myweather.py - external and internal temperature, barometric pressure and internal humidity logger.

Press Ctrl+C to exit!

""")

# Get the temperature of the DS18B20 probe
def get_DS18B20_temperature():
    f = open("/sys/bus/w1/devices/" + probe + "/w1_slave", "r")
    raw_DS18B20_data = f.readline()
    raw_DS18B20_data = f.readline()
    f.close()
    DS18B20_split = raw_DS18B20_data.split('=')
    temp = int(DS18B20_split[1].strip())
    if temp > 1000000:
        temp = temp - 4096000
    return temp

# Get temperature, pressure and humidity in single reading
def get_bme280_all(self):
    self.update_sensor()
    return self.temperature, self.pressure, self.humidity

# Sample data and store
def sampleTick():
    allSensors = get_bme280_all(bme280)
    bme_temp = allSensors[0]
    bme_pres = allSensors[1]
    bme_humi = allSensors[2]
    # Connect to sqlite database
    dbConn = sqlite3.connect(my_database)
    dbCursor = dbConn.cursor()
    if len(probe.strip()) == 15:
        # We have a DS18B20 temperature probe
        DS18B20_temp = get_DS18B20_temperature()
        message = "Int: {:.1f}C Ext: {:.1f}C Pres: {:.1f}hPa Hum: {:.1f}%".format(float(bme_temp), float(
            DS18B20_temp) / 1000, bme_pres, bme_humi)
        dbCursor.execute('''INSERT INTO dataSamples (timestamp, DS18B20_ext, BME280_temp, BME280_pres, BME280_humi)
            VALUES (strftime('%s', 'now'), ?, ?, ?, ?)''', (DS18B20_temp, bme_temp, bme_pres, bme_humi))
        if channel_id != 0:
            channel.update({'field1': float(bme_temp), 'field2': float(bme_pres), 'field3': float(bme_humi),
                        'field4': float(DS18B20_temp) / 1000})
    else:
        # We don't have a DS18B20 temperature probe
        message = "Temp: {:.1f}C Pres: {:.1f}hPa Hum: {:.1f}%".format(float(bme_temp), bme_pres, bme_humi)
        logging.info(message)
        dbCursor.execute('''INSERT INTO dataSamples (timestamp, BME280_temp, BME280_pres, BME280_humi)
            VALUES (strftime('%s', 'now'), ?, ?, ?)''', (bme_temp, bme_pres, bme_humi))
        if channel_id != 0:
            channel.update({'field1' : float(bme_temp), 'field2' : float(bme_pres), 'field3' : float(bme_humi)})
    # Commit the latest data sample to the database and exit
    dbConn.commit()
    dbConn.close()
    return True

if __name__ == "__main__":
    sampleTick()
