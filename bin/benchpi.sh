#!/bin/bash
while [[ true ]]; do
	php benchpi.php & > /dev/null
	php benchpi.php  > /dev/null
	rpi_temperature.sh graph
done
