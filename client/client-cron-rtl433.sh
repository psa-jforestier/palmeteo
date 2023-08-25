#!/bin/bash
# This script has to be planned on a crontab.
# the crontab must planned this script every TIMEOUT_RECORDER+TIMEOUT_SENDER.


# Maximum duration of the recording process 
export TIMEOUT_RECORDER=30

# Approximous time to launch RTL_FM
export RTLFM_TIME_OVERHEAD=4

# Maximum duration of the sending process
export TIMEOUT_SENDER=120

cd $(dirname "$0")

echo "====================================="
date
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
		OWM_RAIN=$(cat /tmp.ram/openweathermap.dat | jq '.rain["1h"]')
		if [ "$OWM_RAIN" == "null" ]
		then
			OWM_RAIN=0
		fi
		(cd ../server/ && php windgraph.php -openweathermapfile /tmp.ram/openweathermap.dat > /usr/share/rpimonitor/web/img/wind.png)
		(cd ../server/ && php windcli.php -openweathermapfile /tmp.ram/openweathermap.dat -windhisto windhistory.csv -windrosedata /usr/share/rpimonitor/web/img/windRoseData.csv )
		echo {\"time\":\"$(date -d @$OWM_DATE +%Y-%m-%d' '%H:%M:%S)\", \"brand\":\"openweathermap\", \"model\":\"$(hostname)\", \"id\":\"OWM\", \"battery\":\"OK\", \"newbattery\":\"0\", \"temperature_C\":$OWM_TEMP, \"humidity\":$OWM_HUMI, \"rain_mm\":$OWM_RAIN} | tee -a /tmp.ram/weather.dat
	fi


	#echo $(date +%Y-%m-%d' '%H:%M:%S), $(date +%s), PI, $(./rpi_temperature.sh | cut -c 6-9), nan, 0, \(0/0\). | tee -a /tmp.ram/weather.dat
	echo {\"time\":\"$(date +%Y-%m-%d' '%H:%M:%S)\", \"brand\":\"raspberry\", \"model\":\"$(hostname)\", \"id\":\"PI\", \"battery\":\"OK\", \"newbattery\":\"0\", \"temperature_C\":$(./rpi_temperature.sh | cut -c 6-9) } | tee -a /tmp.ram/weather.dat

    # old rtl433 client :
	# ../bin/rtl_433 -G -g 50 -f 868300000 -F json -T $TIMEOUT_RECORDER | tee -a /tmp.ram/weather.dat
	
	# new rtl433 client :
	/home/jerome/rtl_433.2023/build/src/rtl_433 -f 868300000 -s 250k -F http -F json -T $TIMEOUT_RECORDER | tee -a /tmp.ram/weather.dat
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
	echo :: Start at $(date)
	retrylater=0

		
	timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat -f json
	export ret=$?
	echo :: End at $(date)
	if [ "$ret" -eq 0 ]
	then
		echo "Data successfully send to remote server"
	else
		echo "Error $ret while sending to remote server, will retry later"
		# err 124 is a timeout error
		retrylater=1
	fi
	
	timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat -f json --output folder /tmp.ram/
	export ret=$?
	if [ "$ret" -eq 0 ]
	then
		echo "Data successfully send to local file server"
	else
		echo "Error $ret while sending to local file server, will retry later"
		retrylater=1
	fi
	
	if [ "$retrylater" -eq 0 ]
	then
		echo
		echo "All data send successfully"
		# Reset data file
		cp /tmp.ram/weather.dat /tmp.ram/weather.last.dat
		> /tmp.ram/weather.dat
	else
		echo
		echo "At least one error has occured, retry later"
	fi
	
	
        
	# check if rpimonitor is still working. Should reply 401 or 200
	curl http://localhost:8888/ --max-time 5 -s
	ret="$?"
	if [ "$ret" -ne "0" ]
	then
		sudo service rpimonitor restart
	fi
	echo
	date

