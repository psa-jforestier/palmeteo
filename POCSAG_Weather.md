# POCSAG Weather data decoding

! This is widely work in progress !

Some weather station (Lacrosse Starmeteo) can receive weather forecast and time synchronization without being connected to internet. They do not use DCF77 or other low-frequency time sync. It seems they use the POCSA protocol on 466MHz.

## About POCSAG

POCSAG protocol is a regional UHF (466Mhz) protocol . A POCSAG transmitter send signal to maybe 50Km at max. So there is a mesh of POCSAG transmitter all over the country. They are connected to each other via Satellite link or internet.
This is a commercial service. A data provider (like Lacrosse) pay to the POCSAG provider some fees to spread its data messages, depending on the area it wants to cover.

Each POCSAG data frame is made of :
- a **address or Channel Access Protocol code (CAPCode) ** which is a number identifying like a client or a receiver (every receiver sensible to address 1234 will be able to handle message sent to 1234).
- a message, which can be text or number.

## Receiver POCSAG data frame

With a rtl-sdr station 1 (SDR# + PDW as the POCSAG decdoer) and an OpenWebRX station 2, separated 200km from each other, I noticed the following thing :

- Because this stations are far from each other, weather forecast should be different
- But the time synchro must be the same

After listening for hours this two machine, I noticed some data frame that are good candidate to explore the Lacrosse / StarMeteo protocol :
- POCSAG channel is 466,205 MHz
- Address is 0025176 : this is the only adress sending cabalistics messages at regular interval

So I setup this two listening station to focus on 466.205MHz and receive only message sent to address 0025176.

Some data are in common between Station 1 and Station 2 and are received in less than 10s. Examples :
```
Station 1 - SDR#
12:21:09 11-08-23 1+!.:%p-& )''39ED-'0T!&49%8"F0("F69E8s&0,s&;
12:41:11 11-08-23 1+!.:%p-& )''39ED-'0T!&49%8"F0("F69E8s&0,s&;
Station 2 - OpenWebRX
12:21:14 11-08-23 1+!.:%p-& )''39ED-'0T!&49%8"F0("F69E8s&0,s&;
12:41:13 11-08-23 1+!.:%p-& )''39ED-'0T!&49%8"F0("F69E8s.6,s&;
```
20 mn from each other, but they are exactly the same. It could not be a time synchronization.

Some frames are differents, send in burst, but look similar :
```
Station 1 - SDR#
12:31:09 11-08-23 ZH<2H:HBHRI"I*I:IZK)
12:31:24 11-08-23 ZH<2H:HBHRI"I*I:IZK)
12:31:37 11-08-23 ZH<2H:HBHRI"I*I:IZK)
12:31:52 11-08-23 ZH<2H:HBHRI"I*I:IZK)
Station 2 - OpenWebRX
12:31:13 11-08-23 kK<:HJHZI"I*I2I:IJJ"J*JBHI
12:31:27 11-08-23 kK<:HJHZI"I*I2I:IJJ"J*JBHI
12:31:41 11-08-23 kK<:HJHZI"I*I2I:IJJ"J*JBHI
12:31:54 11-08-23 kK<:HJHZI"I*I2I:IJJ"J*JBHI
```

I'm still looking for some data frame that may match my assumptions : is should have a region identifier (different from Station1 to Station2), data in common (for time sync), different data for weather forecast.

The time sync frame should be sent with small variation, but I dont know how many times per hour they are sent.
