#!/bin/sh
while [ true ]
do	
	echo ==============
	date
	echo $(date +%Y-%m-%d' '%H:%M:%S), $(date +%s), PI, $(rpi_temperature.sh | cut -c 6-9), nan, 0, \(0/0\). | tee -a /tmp.ram/weather.dat
	timeout 40 ../bin/rtl_fm -R 30 -f 868000000 -M fm -s 500k -r 75k -g 42 -A fast | ../bin/rtl_868 | tee -a /tmp.ram/weather.dat
	echo :: Datafile is :
	ls -la /tmp.ram/weather.dat
	echo :: It contains $(cat /tmp.ram/weather.dat | wc -l) line
	echo :: Send it to the server
	php client.php /tmp.ram/weather.dat
	echo
	sleep 20
	#rm /tmp.ram/weather.dat
	> /tmp.ram/weather.dat
done
