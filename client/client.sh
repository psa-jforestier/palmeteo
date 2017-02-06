#!/bin/sh
while [ true ]
do	
	date
	timeout 50 rtl_fm -f 868.26e6 -M fm -s 500k -r 75k -g 42 -A fast - | ../bin/rtl_868 > /tmp.ram/weather.dat
	echo Datafile is :
	ls -la /tmp.ram/weather.dat
	sleep 10
done
