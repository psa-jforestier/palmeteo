#!/bin/sh
# This script has to be planned on a crontab.
# the crontab must planned this script every TIMEOUT_RECORDER+TIMEOUT_SENDER.


# Maximum duration of the recording process 
export TIMEOUT_RECORDER=30

# Approximous time to launch RTL_FM
export RTLFM_TIME_OVERHEAD=4

# Maximum duration of the sending process
export TIMEOUT_SENDER=20

cd $(dirname "$0")
# If duration is 30s, the script must be planned every minute

	echo ==============
	date
	echo $(date +%Y-%m-%d' '%H:%M:%S), $(date +%s), PI, $(./rpi_temperature.sh | cut -c 6-9), nan, 0, \(0/0\). | tee -a /tmp.ram/weather.dat
	timeout $TIMEOUT_RECORDER \
		../bin/rtl_fm -R $(($TIMEOUT_RECORDER-$RTLFM_TIME_OVERHEAD)) -f 868000000 -M fm -s 500k -r 75k -g 42 -A fast | \
		../bin/rtl_868  | \
		tee -a /tmp.ram/weather.dat
	echo :: Datafile is :
	ls -la /tmp.ram/weather.dat
	echo :: It contains $(cat /tmp.ram/weather.dat | wc -l) line
	echo :: Send it to the server
	timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat && timeout $TIMEOUT_SENDER php client.php /tmp.ram/weather.dat --output wu 
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
		echo
		# keep datafile and send it for the next run
	fi
	#rm /tmp.ram/weather.dat
	
