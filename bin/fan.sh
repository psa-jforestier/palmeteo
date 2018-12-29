#!/bin/bash
if [[ "$1" == "graph" ]]; then
	TEMP=$(cat /sys/class/thermal/thermal_zone0/temp)
	DECITEMP=$(echo $TEMP/1000 | bc -l | cut -c 1-5)
	echo -n $DECITEMP
	TEMP=$(($TEMP/1000))
	for (( i=1; i <= $TEMP; i++ )) 
	do
		echo -n "#"
	done
	echo
else
	vcgencmd measure_temp
fi
