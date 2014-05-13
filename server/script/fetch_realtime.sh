#!/bin/bash
#desc: 抓取股票每日总览数据
#author: fox
#date: 2013-08-12

main()
{
    day=`date "+%Y%m%d"`
    interval=10

    if [ $# -ge 1 ]
    then
        day=$1
    fi
    if [ $# -ge 2 ]
    then
        interval=$2
    fi

    open=`is_market_open "$day"`
    echo "day=$day open=$open"
    if [ "$open" == "0" ]
    then
        exit
    fi

    cd $STOCK_SCRAPY_PATH
    result_path=$STOCK_SCRAPY_PATH/data/$day
    filename="poollist.txt"
    log="daily_realtime_$day.log"
    echo "log=$log filename=$filename"

    # 用scrapy抓取总览信息
    count=0
    /usr/local/bin/redis-cli -h 127.0.0.1 get "poollist-$day" | sed 's/"//g' | sed "s/{//g" | sed "s/}//g" | sed "s/,/\n/g" | sed "s/:/\t/g" |
    while read line
    do
        sid=`echo $line | awk '{print $1}'`
        scode=`echo $line | awk '{print $2}'`
        echo "sid=$sid scode=$scode"
        $SCRAPY_BIN crawl qq -a id=$sid -a code=$scode -a "start"=0930 -a redis_host=127.0.0.1 -a redis_port=6379 --logfile=$result_path/fetch_realtime.log >> $result_path/$log &

        count=`expr $count + 1`
        mod=`expr $count % $interval`
        if [ $mod -eq 0 ]
        then
            sleep 10
        fi
    done #< $result_path/$filename

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
