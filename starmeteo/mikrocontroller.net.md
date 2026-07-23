Translated from https://www.mikrocontroller.net/topic/464834

```
The station is supposed to be on the frequency 465,970MHz the weather information 
received.

The frequencies 466,075MHz and 466,230MHz are also from the E*Message 
GmbH operated.

I have the frequency for the test with a radio scanner and a software 
465,970MHz observed.
*=*=*
I logged the pager frequencies for a long time.
The 465,970MHz is as not suspected.
I think it's the 466,230MHz. (formerly Scall)
*=*=*
Oh, I was right with my guess!
466,230MHz.
So 1200bit/s and not 512bit/s as first assumed.
*=*=*
After inserting the battery, the time is displayed quite quickly, which 
Weather information takes much longer (up to 6 hours). That's the clock 
opposite a radio clock by approx. 40 seconds, the time will 
Probably also set via the pager service and not via DCF77.
*=*=*
> However, if you record the circuit, you should quickly
> getting out which chip this is about.
You can probably save yourself. I'm after some search on the 
AX50424 by AXSEM.
https://www.cdiweb.com/datasheets/axsem/ax50424_ds_v1_3.pdf
Now belongs to ON Semi, which only a number of transceivers of this 
Type offer, no more pure receivers. Because the same is the 
AX5243 as TRX. Apparently, the package was later reduced from 28 to 
20 pins if you leave out the NC and TST1..3 pins. The data sheet is 
from 2008, so comes well in time with the technology of the weather station.
The application circuit on page 32 in the data sheet is correct 
few little things quite exactly with yours agrees on what antenna, quartz, 
SPI and power supply concern. Some NC pins are with you 
However, as test points.
When sniffing at the SPI, you may be able to set the exact frequency, 
Get bandwidth, filters, etc. out. And of course encrypted 
Data Packets :)
*=*=*
A short test with POC32 on 466.230 MHz delivers at the address 
0002504 the time (at 1200 baud):
23.10.2023 14:16:19  1200 Baud   0002504    141529   231023
23.10.2023 14:19:07  1200 Baud   0002504    141929   231023
23.10.2023 14:19:15  1200 Baud   0002504    141929   231023
*=*=*
If the outside temperature sensor was received or after a timeout 
is determined the frequency for the weather data. To do this, the 
Receive the following frequencies:
466,230 MHz
466,075 MHz
465,970 MHz
*=*=*
The weather data for the "WETTERdirekt 50" variant come from address 
1208128, the time as already written by address 0002504. The 
Weather data comes in several parts, probably in three 
Separate messages.
*=*=*
```
