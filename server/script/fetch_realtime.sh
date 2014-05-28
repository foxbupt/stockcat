#!/bin/bash
#desc: 抓取股票实时交易数据
#author: fox
#date: 2013-08-12

main()
{
    day=`date "+%Y%m%d"`

    if [ $# -ge 1 ]
    then
        day=$1
    fi

    open=`is_market_open "$day"`
    echo "day=$day open=$open"
    if [ "$open" == "0" ]
    then
        exit
    fi

    slist=`/usr/local/bin/redis-cli -h 127.0.0.1 get "poollist-$day" | sed 's/"//g' | sed "s/{//g" | sed "s/}//g" | sed "s/,/\n/g"`
    /usr/bin/python $SERVER_PATH/lib/fetch.py $SERVER_PATH/lib/config.ini "realtime" $slist

    #do
    #    sid=`echo $line | awk '{print $1}'`
    #    scode=`echo $line | awk '{print $2}'`
    #    echo "sid=$sid scode=$scode"
    #    $SCRAPY_BIN crawl qq -a id=$sid -a code=$scode -a "start"=0930 -a redis_host=127.0.0.1 -a redis_port=6379 --logfile=$result_path/fetch_realtime.log >> $result_path/$log &

    #    count=`expr $count + 1`
    #    mod=`expr $count % $interval`
    #    if [ $mod -eq 0 ]
    #    then
    #        sleep 10
    #    fi
    #done #< $result_path/$filename

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
