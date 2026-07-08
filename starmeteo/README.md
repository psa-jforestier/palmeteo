An attempt to create a set of tool to reborn your La Crosse / StarMeteo weather station.

Mostly based on work available from https://forum.tetrahub.net/decodage-meteo/meteofrance-pocsag-t5935.html

Roadmap :
1/ Understand data-flow
90% done
Meteo France ==> e*message ==> POCSAG broadcast on 466.205MHz ==> Weather stations ==> microcontroler ==> LCD display

2/ Understand protocol
75% done (see previous forum)
I'm still questionning why the protocol seems to complicated and unlogical. We are probably missing something (encyption layer).
Not all data are decoded.
Currently decoded : time&date, temperature forecast, cloud forecast
To be decoded : alert

3/ Re-implement protocol
50% done

4/ Re-implement a data flow
