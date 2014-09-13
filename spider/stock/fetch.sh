#!/bin/bash
#desc: 批量抓取股票数据
#author: fox
#date: 2013-08-01

#STOCK_LIST=/home/fox/svnroot/codes/stockcat/web/data/stock_list.txt

if [ $# -lt 3 ]
then
    echo "Usage: $0 <filename> <start_date> <end_date>"
    exit
fi

filename=$1
start_date=$2
end_date=$3

while read line
do
    id=`echo $line | awk '{print $1}'`
    code=`echo $line | awk '{print $2}'`
    filename="$code.json"

    /usr/bin/scrapy crawl "ifeng" -a id=$id -a code=$code -a start_date=$start_date -a end_date=$end_date -o /data/stockcat/hist/$filename
    echo "op=fetch_history_data id=$id code=$code start_date=$start_date end_date=$end_date"

    #year=$start_year
    #while [ $year -le $end_year ]
    #do
    #    start_date="$year-1-1"
    #    end_date="$year-8-11"

    #    /usr/bin/scrapy crawl "ifeng" -a id=$id -a code=$code -a start_date=$start_date -a end_date=$end_date -o data/$filename
    #    echo "op=fetch_history_data id=$id code=$code year=$year start_date=$start_date end_date=$end_date"
    #    year=`expr $year + 1`
    #done

    sleep 2
done < $filename

echo "finish"
