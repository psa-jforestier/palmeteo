#!/bin/bash

show_help() {
	echo "Usage : $0 [on] [off] [test] [auto --temp XX]"
}

fan_off()
{
	gpio -g write $GPIO_BCM_NUM 0
	echo "0" > $TMP_FAN_STATUS
}

fan_on()
{
	gpio -g write $GPIO_BCM_NUM 1
	echo "1" > $TMP_FAN_STATUS
}

if [[ "$1" == "" || "$1" == "-h" || "$1" == "--help" ]]; then
	show_help
	exit 0
fi

# GPIO number is based on Name or wPi reference when using "gpio readall"
# This is not the BCM or physical number.
# example : GPIO_NUM = 4 => wPi = 4 => BCM = 23 => physical pin on the connector = 16
GPIO_BCM_NUM=23

TMP_FAN_STATUS=/tmp.ram/fan.gpio.${GPIO_NUM}

alias datef='date "+%F %T"'

gpio -g mode $GPIO_BCM_NUM out

if [[ "$1" == "test" ]]; then
	echo "Testing the fan on GPI $GPIO_NUM"
	echo "Press ^C to quit"
	while [[ true ]]; do
		echo "ON"
		fan_on
		sleep 4
		echo "OFF"
		fan_off
		sleep 4
	done
fi

if [[ "$1" == "on" ]]; then
	echo "Fan is ON"
	fan_on
	exit 0
fi

if [[ "$1" == "off" ]]; then
       echo "Fan is OFF"
	fan_off
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
		fan_on
	else
		if [[ $CURRENT_FAN == 1 ]]; then
			echo "$(date) : current temp $CURRENT_TEMP is < $TEMP : turn OFF fan"
		fi
		fan_off
	fi
fi

