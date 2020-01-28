#!/bin/bash

if [ -f .callback_daemon_status.txt ]; then
	:
else 
	echo 00 > .callback_daemon_status.txt
fi

e=0

if [[ $1 == "disable" || $1 == "0" || $1 == "off" ]]; then
	
	if cat .callback_daemon_status.txt | grep '^1' > /dev/null; then
		echo "Stopping..."
	fi

	if cat .callback_daemon_status.txt | grep '^[01]1' > /dev/null; then
		echo 01 > .callback_daemon_status.txt
	fi

	e=1

elif [[ $1 == "enable" || $1 == "1" || $1 == "on" ]]; then
	
	if cat .callback_daemon_status.txt | grep '^11' > /dev/null; then
		:
	elif cat .callback_daemon_status.txt | grep '^01' > /dev/null; then
		echo "Waiting for daemon to be stopped..."
		d=0
		while [ $d -le 100 ]
		do
			if cat .callback_daemon_status.txt | grep '^00' > /dev/null; then
				d=101
			else
				sleep 1
			fi
		done
	fi

	if cat .callback_daemon_status.txt | grep '^00' > /dev/null; then
		echo "Starting..."
		echo 11 > .callback_daemon_status.txt
		(
			c=0
			while [ $c -le 100 ]
			do
				if cat .callback_daemon_status.txt | grep '^1' > /dev/null; then
					php callback_daemon.php
					sleep 5
				else
					echo 00 > .callback_daemon_status.txt
					c=101
				fi
			done
		) </dev/null >/dev/null 2>&1 &
		disown	
	fi
fi



if cat .callback_daemon_status.txt | grep '^[01]1' > /dev/null; then
	if [[ $e -le 0 ]]; then
		echo "Daemon running!"
	fi
else 
	echo "Daemon stopped!"
fi
