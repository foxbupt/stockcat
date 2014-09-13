#!/bin/bash
#desc: 每天早上启动所有服务
#author: fox
#date: 2014/07/12

main()
{
    day=`date "+%Y%m%d"`
    weekday=`date "+%w"`

    if [ $# -ge 1 ]
    then
        day=$1
    fi

    lastday=`date -d "1 day ago" +%Y%m%d`
    ./del_key.sh "*${lastday}*" >> /data/stockcat/service/start_${day}.log

    open=`is_market_open "$day"`
    echo "day=$day open=$open"
    if [ "$open" == "0" ]
    then
        exit
    fi

    #count=1
    #if [ $weekday -eq 1 ]
    #then
    #    count=3
    #fi

    ./service.sh restart all
    echo "finish"
}

cd ${0%/*}
. ./comm.inc
main "$@"
