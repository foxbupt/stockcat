#!/bin/bash
#desc: 批量抓取股票数据
#author: fox
#date: 2013-08-01

STOCK_LIST=/home/fox/svnroot/codes/stockcat/web/data/stock_list.txt

if [ $# -lt 2 ]
then
    echo "Usage: $0 <start_year> <end_year>"
    exit
fi

start_year=$1
end_year=$2

while read line
do
    id=`echo $line | awk '{print $1}'`
    code=`echo $line | awk '{print $2}'`
    filename="2013_$code.json"

    year=$start_year
    while [ $year -le $end_year ]
    do
        start_date="$year-1-1"
        end_date="$year-8-9"

        /usr/local/python/bin/scrapy crawl "ifeng" -a id=$id -a code=$code -a start_date=$start_date -a end_date=$end_date -o data/$filename
        echo "op=fetch_history_data id=$id code=$code year=$year start_date=$start_date end_date=$end_date"
        year=`expr $year + 1`
    done

    sleep 2
done < $STOCK_LIST

echo "finish"
