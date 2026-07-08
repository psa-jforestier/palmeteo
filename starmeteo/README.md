An attempt to create a set of tool to reborn your La Crosse / StarMeteo weather station.

Mostly based on work available from https://forum.tetrahub.net/decodage-meteo/meteofrance-pocsag-t5935.html

# Roadmap

## 1/ Understand data-flow

90% done

Goal : undertand which systems interact from end-to-end (from weather forecast to user weather station).

Meteo France ==> weather forecast ==> encode ==> e*message ==> POCSAG broadcast on 466.205MHz ==> Weather stations ==> microcontroler ==> decode ==> LCD display

## 2/ Understand protocol

75% done (see previous forum)

Goal : decode data frames. Be able to get weather info from a data frame.

I'm still questionning why the protocol seems so complicated and unlogical. We are probably missing something (encryption layer). Not easy because a lot of test-and-try and hardware challenges (see forum).

Currently decoded : time&date, temperature forecast, cloud forecast

To be decoded : alert

## 3/ Recode information

75% done

Goal : from a random weather forecast, generate a valid data frame.

Technical challenge here to re-transmit data fram, because the 466MHz band is not a public ISM band. Need to be carefull on transmit power and frequency. 466.205MHz is used by private POCSAG service, it is not allowed to use this frequency.

## 4/ Basic re-implementation

25% done

Goal : create a suite of basic tools to recreate the 1st part of the data flow : Meteo France ==> weather forecast ==> encode

Here, we can use any weather forecast provider, not only Meteo France.

Weather forecast providers : WeatherUnderground and/or OpenMeteo and/or other generic provider (user can use their own forecast)

Tools to be created :
- sm_time : to code or decode a date & time data frame
- sm_forecast : to code or decode a weather forceast data frame
- sm_send : to send POCSAG data frame to the weather station



