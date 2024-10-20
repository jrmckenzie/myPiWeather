#!/usr/bin/python

import logging
import sqlite3
import thingspeak
import os

from bme280 import BME280

try:
    from smbus2 import SMBus
except ImportError:
    from smbus import SMBus

# Set channel_id to your thingspeak channel ID etc.
channel_id = 1234567
write_key  = 'set this to your thingspeak write key'
read_key   = 'set this to your thingspeak read key'

bus = SMBus(1)
bme280 = BME280(i2c_dev=bus)

# Set BME280 sensors to forced mode for long sampling intervals
bme280.setup(mode="forced", temperature_oversampling=1, pressure_oversampling=1, humidity_oversampling=1)

# Set up Thingspeak channel
channel = thingspeak.Channel(id=channel_id, api_key=write_key)

logging.basicConfig(
    format='%(asctime)s.%(msecs)03d %(levelname)-8s %(message)s',
    level=logging.ERROR,
    datefmt='%Y-%m-%d %H:%M:%S')

logging.info("""myweather.py - external and internal temperature, barometric pressure and internal humidity logger.

Press Ctrl+C to exit!

""")

# Get the temperature of the DS18B20 probes
def get_DS18B20_temperature():
    # Set the probe values to the IDs of your DS18B20 probes
    probe = ["28-0114509580aa", "28-0114559c70aa"]
    DS18B20 = []
    for x in probe:
        f = open("/sys/bus/w1/devices/" + x + "/w1_slave", "r")
        raw_DS18B20_data = f.readline()
        raw_DS18B20_data = f.readline()
        f.close()
        DS18B20_split = raw_DS18B20_data.split('=')
        temp = int(DS18B20_split[1].strip())
        if temp > 1000000:
            temp = temp - 4096000
        DS18B20.append(temp)
    return DS18B20

# Get temperature, pressure and humidity in single reading
def get_bme280_all(self):
    self.update_sensor()
    return self.temperature, self.pressure, self.humidity

# Sample data and store to thingspeak channel
def sampleTick():
    allSensors = get_bme280_all(bme280)
    bme_temp = allSensors[0]
    bme_pres = allSensors[1]
    bme_humi = allSensors[2]
    DS18B20_temp = get_DS18B20_temperature()
    message = "BME: {:.1f}C Ext: {:.1f}C Int: {:.1f}C Pres: {:.1f}hPa Hum: {:.1f}%".format(float(bme_temp), float(DS18B20_temp[0])/1000, float(DS18B20_temp[1])/1000, bme_pres, bme_humi)
    logging.info(message)
    channel.update({'field1' : float(bme_temp), 'field2' : float(bme_pres), 'field3' : float(bme_humi), 'field4' : float(DS18B20_temp[1])/1000, 'field5' : float(DS18B20_temp[0])/1000})

# Initialize sampling and dispatch data logger
sampleTick()
