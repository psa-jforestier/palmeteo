# La Crosse temperature and hygrometer sensor TX35DTH-IT

From the wide sensor catalog of La Crosse, there is a cheap one doing temperature and hygrometer, sending data in 868MHz, with a protocol supported by rtl_433. The TX35DTH-IT is one of them. It is branded "Starmeteo", but is made by La Crosse Technology. The battery last more or less two years. It have a small LCD display indicating temperature (in °C) and hygrometry / humidity (in %).

This sensor us the "Instant Transmission" protocol, indicating data are sent every 6 to 10s to the weather station.

You may find it under reference TX35DTH or other brands.

## Protocol

It uses OOK (on-off keying) transmission on 868.3MHz. With a RTL-SDR dongle, you can capture the data transmission. Go to https://github.com/merbanan/rtl_433/blob/master/src/devices/lacrosse_tx35.c to see the details, but in a nutshell, the data are :
- a preamble alternatively sending 0 and 1
- a sync word used to synchronize receiver (data is 0x2DD4)
- the sensor model on 4 bits (value 9 for this model)
- a device identifier, on 6 bits, chenged every time you change the battery
- a new battery bit (1 indicate the battery has been changed). It is set for 5 minutes.
- an unkown bit
- the temperature in 12 bits (first nibble is the ten, second is the unit, third is the .1. Remove 40 to have the °C)
- a low battery bit (1 indicate the battery should be changed)
- humidity on 7 bits (0x6A : no humidity sensor)
- CRC on 8 bits (CRC8 poly 0x31 init 0x00)

## Receiving data
With the RTL_433 21.05 or newer, to receive the data from this sensor, use :
```
$> rtl_433 -f 868.3M -R 75 
rtl_433 version 21.12 branch  at 202112141644 inputs file rtl_tcp RTL-SDR
Use -h for usage help and see https://triq.org/ for documentation.
(...)

New defaults active, use "-Y classic -s 250k" for the old defaults!

Registered 1 out of 207 device decoding protocols [ 75 ]
Found Rafael Micro R820T tuner
Exact sample rate is: 1000000.026491 Hz
[R82XX] PLL not locked!
Sample rate set to 1000000 S/s.
Tuner gain set to 20.700000 dB.
Tuned to 868.300MHz.
baseband_demod_FM: low pass filter for 1000000 Hz at cutoff 200000 Hz, 5.0 us

time      : 2023-06-29 09:17:03
model     : LaCrosse-TX35DTHIT                     id        : 6
Battery   : 0            NewBattery: 0             Temperature: 26.8 C       Humidity  : 56 %          Integrity : CRC

time      : 2023-06-29 09:17:13
model     : LaCrosse-TX35DTHIT                     id        : 6
Battery   : 0            NewBattery: 0             Temperature: 26.8 C       Humidity  : 56 %          Integrity : CRC
```
Here you can see my sensor ID 6 measure a temperature of 26.8°C and a humidity of 56%.
