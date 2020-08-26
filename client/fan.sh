#!/bin/bash

show_help() {
	echo "Usage : $0 [on] [off] [test] [auto --temp XX]"
}

if [[ "$1" == "" || "$1" == "-h" || "$1" == "--help" ]]; then
	show_help
	exit 0
fi

GPIO_NUM=4
TMP_FAN_STATUS=/tmp.ram/fan.gpio.${GPIO_NUM}

alias datef='date "+%F %T"'

gpio mode $GPIO_NUM out

if [[ "$1" == "test" ]]; then
	echo "Testing the fan on GPI $GPIO_NUM"
	echo "Press ^C to quit"
	while [[ true ]]; do
		echo "ON"
		gpio write $GPIO_NUM 1
		echo "1" > $TMP_FAN_STATUS
		sleep 4
		echo "OFF"
		gpio write $GPIO_NUM 0
		echo "0" > $TMP_FAN_STATUS
		sleep 4
	done
fi

if [[ "$1" == "on" ]]; then
	echo "Fan is ON"
	gpio write $GPIO_NUM 1
	echo "1" > $TMP_FAN_STATUS
	exit 0
fi

if [[ "$1" == "off" ]]; then
       echo "Fan is OFF"
       gpio write $GPIO_NUM 0
       echo "0" > $TMP_FAN_STATUS
       exit 0
fi

if [[ "$1" == "auto" ]]; then
	if [[ "$2" != "--temp" ]]; then
		echo "Missing --temp XX parameters"
		show_help
		exit 1
	fi
	TEMP="$3"
	if [[ "$TEMP" == "" ]]; then
		echo "$TEMP : invalid value. Temp is an absolute number in °C"
		show_help
		exit 2
	fi
	CURRENT_FAN=$(cat $TMP_FAN_STATUS)
	TEMP="${TEMP}000"
	CURRENT_TEMP=$(cat /sys/class/thermal/thermal_zone0/temp)
	if [ $CURRENT_TEMP -gt $TEMP ]
	then
		if [[ $CURRENT_FAN == 0 ]]; then
			echo "$(date) : current temp $CURRENT_TEMP is > $TEMP : turn ON fan"
		fi
		gpio write $GPIO_NUM 1
		echo "1" > $TMP_FAN_STATUS
	else
		if [[ $CURRENT_FAN == 1 ]]; then
			echo "$(date) : current temp $CURRENT_TEMP is < $TEMP : turn OFF fan"
		fi
		gpio write $GPIO_NUM 0
		echo "0" > $TMP_FAN_STATUS
	fi
fi

