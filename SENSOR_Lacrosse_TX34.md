# La Crosse rain meter TX34DTH-IT

This sensor, from La Crosse, is bundled in several weather station. I got mine from the WS-9004 rain-only weather station, including the sensor and a little LCD receiver. I paid 10€ on Le Bon Coin for a new (never opened) station.
The sensor follow the IT+ technology ("instant transmission"), and is sending data on 868MHz, so a perfect sensor for the Raspberry Pi + RTL_433 couple. Data are send every 6 to 10s to the station.

## How it works

This rain sensor use the "auget" technlogiy : two little buckets get the rain, and each time a bucket switch from a position to an other, an internal counter is incremented. To detect a switch, the sensor use a magnetic interruptor, called Reed switch. There is a little magnet on the bucket, each time it pass in front of the Reed switch, we can heard a small "click" and the sensor know the bucket have switched.

### Rain meter not reliable

Unfortunatly, this series of rain sensor from La Crosse is well know to be unreliable, and mine was in fact not working at all when I set up all the this at the first time. The base station did not detect or count the number of switch. This is due to the fact that the Reed sensor is not triggered by the magnet because they are too far from each other. I did not hear the internal "click" when the bucket switch. To fix this, I dismount the rain sensor with a little soldering with a strap, I was able to increase the length of the Reed switch and set it closer to the platic panel. It now detects a switch every time.

### Measuring

The user manual indicate that the LCD receiver count the amount of rainfall in mm (milli-meter). A better definition is that it counts the height of water falled in a one meter square. So we measure water in meter, not in litter, which can be strange at first look. So I did some measure by myself :
- the rain recevier is 10cm x 3,5cm = 35 cm²
- I measured that a switch of the auget is made when a bucket is filled with 2ml (milli-litter)
- So there is a witch for 0,002/0,0035 = 0,571 litter per m².
By the way, the LCD display provided with the sensor add a one to the measure only when there is 2 switchs of the bucket.

## Protocol

It uses OOK (on-off keying) transmission on 868.3MHz. With a RTL-SDR dongle, you can capture the data transmission. Go to https://github.com/merbanan/rtl_433/blob/master/src/devices/lacrosse_tx34.c to see the details, but in a nutshell, the data are :
- a preamble alternatively sending 0 and 1
- a sync word used to synchronize receiver (data is 0x2DD4)
- the sensor model on 4 bits (value 5 for this model)
- a device identifier, on 6 bits, changed every time you change the battery
- a low battery bit (1 indicate the battery should be changed)
- a new battery bit (1 indicate the battery has been changed). It is set for a long time (several hours) minutes.
- 15 bits of bucket switch counter
- CRC on 8 bits (CRC8 poly 0x31 init 0x00)

The protocol send the real number of switch. So to have the number of mm of rain, like the LCD pannel, you are supposed to divid it by two.

## Receiving data
With the RTL_433 21.12 or newer, to receive the data from this sensor, use :
```
$> rtl_433 -f 868300000 -R 206
rtl_433 version nightly-4-g767e5387 branch master at 202306081919 inputs file rtl_tcp RTL-SDR with TLS
Use -h for usage help and see https://triq.org/ for documentation.
(...)
[Protocols] Registered 17 out of 245 device decoding protocols [ 206 ]
Found Rafael Micro R820T tuner
[SDR] Using device 0: Realtek, RTL2838UHIDIR, SN: 00000001, "Generic RTL2832U OEM"
Exact sample rate is: 1000000.026491 Hz
[R82XX] PLL not locked!
_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _
time      : 2023-07-03 14:43:29
model     : LaCrosse-TX34IT                        id        : 35
Battery   : 1            New battery: 0            Total rain: 33.744        Raw rain  : 152           Integrity : CRC
_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _
time      : 2023-07-03 14:43:41
model     : LaCrosse-TX34IT                        id        : 35
Battery   : 1            New battery: 0            Total rain: 33.744        Raw rain  : 152           Integrity : CRC
```
Here you can see my sensor ID 35 measure 152 times a bucket switch.
