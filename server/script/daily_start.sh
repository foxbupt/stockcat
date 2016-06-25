#!/bin/bash
#desc: 每天早上启动所有服务
#author: fox
#date: 2014/07/12

main()
{
    location=1
    if [ $# -ge 1 ]
    then
        location=$1
    fi
    
    day=`get_curday "$location"`
    if [ $# -ge 2 ]
    then
        day=$2
    fi

    lastday=`date -d "1 day ago" +%Y%m%d`    
    
    # 日志按天切割 
    if [ -f  /data/stockcat/service/service.log ]
    then
        mv /data/stockcat/service/service.log /data/stockcat/service/service_$(lastday)_${location}.log 
        mv /data/stockcat/service/dump.log /data/stockcat/service/dump_$(lastday)_${location}.log    
    fi
    ./del_key.sh "*${lastday}*" >> /data/stockcat/service/start_${day}.log

    open=`is_market_open "$day"`
    echo "day=$day open=$open"
    if [ "$open" == "0" ]
    then
        exit
    fi

    if [ $# -ge 2 ]
    then
        ./service.sh restart all "$location" "$day"
    else
        ./service.sh restart all "$location" 
    fi
    echo "finish"
}

cd ${0%/*}
. ./comm.inc
main "$@"
