#!/bin/bash
# This script has to be planned on a crontab.
# the crontab must planned this script every TIMEOUT_RECORDER+TIMEOUT_SENDER.


# Maximum duration of the recording process 
export TIMEOUT_RECORDER=30

# Approximous time to launch RTL_FM
export RTLFM_TIME_OVERHEAD=4

# Maximum duration of the sending process
export TIMEOUT_SENDER=60

cd $(dirname "$0")
# If duration is 30s, the script must be planned every minute

	# Load config var from PHP scripts
	OWM_CITYID=$(php -r "include 'config.php'; echo @\$CONFIG['openWeatherMap.cityId'];")
	OWM_APPID=$( php -r "include 'config.php'; echo @\$CONFIG['openWeatherMap.appId'];")
	if [ "$OWM_APPID" ==  "" ] || [ "$OWM_CITYID" == "" ]
	then
		echo "No OpenWeatherMap credential found in config.php . Also, install 'jq'."
	else
		curl -s "https://api.openweathermap.org/data/2.5/weather?units=metric&id=$OWM_CITYID&appid=$OWM_APPID" > /tmp.ram/openweathermap.dat
		OWM_TEMP=$(cat /tmp.ram/openweathermap.dat | jq '.main.temp')
		OWM_HUMI=$(cat /tmp.ram/openweathermap.dat | jq '.main.humidity')
		OWM_DATE=$(cat /tmp.ram/openweathermap.dat | jq '.dt')

		echo {\"time\":\"$(date -d @$OWM_DATE +%Y-%m-%d' '%H:%M:%S)\", \"brand\":\"openweathermap\", \"model\":\"$(hostname)\", \"id\":\"OWM\", \"battery\":\"OK\", \"newbattery\":\"0\", \"temperature_C\":$OWM_TEMP, \"humidity\":$OWM_HUMI} | tee -a /tmp.ram/weather.dat
	fi


	echo ==============
	date
	#echo $(date +%Y-%m-%d' '%H:%M:%S), $(date +%s), PI, $(./rpi_temperature.sh | cut -c 6-9), nan, 0, \(0/0\). | tee -a /tmp.ram/weather.dat
	echo {\"time\":\"$(date +%Y-%m-%d' '%H:%M:%S)\", \"brand\":\"raspberry\", \"model\":\"$(hostname)\", \"id\":\"PI\", \"battery\":\"OK\", \"newbattery\":\"0\", \"temperature_C\":$(./rpi_temperature.sh | cut -c 6-9) } | tee -a /tmp.ram/weather.dat
#	timeout $TIMEOUT_RECORDER \
#		../bin/rtl_fm -R $(($TIMEOUT_RECORDER-$RTLFM_TIME_OVERHEAD)) -f 868000000 -M fm -s 500k -r 75k -g 42 -A fast | \
#		../bin/rtl_868  | \
#		tee -a /tmp.ram/weather.dat
    # old rtl433 client :
	# ../bin/rtl_433 -G -g 50 -f 868300000 -F json -T $TIMEOUT_RECORDER | tee -a /tmp.ram/weather.dat
	
	# new rtl433 client :
	/home/jerome/rtl_433/build/src/rtl_433 -f 868300000 -F json -T $TIMEOUT_RECORDER | tee -a /tmp.ram/weather.dat
	ret="${PIPESTATUS[0]}"
	echo "RTL_433 returned with value $ret"
	if [ "$ret" -eq 2 ] || [ "$ret" -eq 3 ] || [ "$ret" -eq 5 ] 
	then
		# sometime, the rtl dongle crash, we need to reset it. Identify bus (001) and device (004) with lsusb
		echo "Reset USB device, it should be ok for the next run"
		sudo usbreset /dev/bus/usb/001/004
	fi
    
	echo :: Datafile is :
	ls -la /tmp.ram/weather.dat
	echo :: It contains $(cat /tmp.ram/weather.dat | wc -l) line
	echo :: Send it to the server
	timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat -f json && \
		timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat -f json --output folder /tmp.ram/ #&& \
#		timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat -f json --output wu && \
#		timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat -f json --output owm
	export ret=$?
	if [ $ret -eq 0 ]
	then
		echo
		echo "All data send successfully"
		# Reset data file
		cp /tmp.ram/weather.dat /tmp.ram/weather.last.dat
		> /tmp.ram/weather.dat
	elif [ $ret -eq 1 ]
	then
		echo
		echo "Only one error has occured"
		# Reset data file
		cp /tmp.ram/weather.dat /tmp.ram/weather.last.dat
		> /tmp.ram/weather.dat
	elif [ $ret -eq 2 ]
	then
		echo
		echo "Several errors has occured"
		# keep datafile and send it for the next run
	elif [ $ret -eq 124 ]
	then
		echo "Something went wrong when sending data to the server"
		# keep datafile and send it for the next run
	fi
	#rm /tmp.ram/weather.dat
	
	date

