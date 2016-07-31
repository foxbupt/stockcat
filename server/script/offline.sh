#!/bin/bash

if [ $# -lt 3 ]
then
    echo "Usage: $0 <location> <start_day> <end_day>"
    exit
fi

location=$1
day=$2
while [ $day -le $3 ]
do
    /usr/bin/python /home/fox/web/stockcat/server/lib/offline_handler.py /home/fox/web/stockcat/server/lib/fetch.ini $location $day >> /data/stockcat/service/offline_${location}.log
    echo "day=$day location=$location"
    day=`expr $day + 1`
done
