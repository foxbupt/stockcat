#!/bin/bash
#desc: 分析股实时上涨因子
#author: fox
#date: 2014-06-07

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

    slist=`sed "s/\t/:/g" $STOCK_LIST`
    #echo "$slist"
    /usr/bin/python $SERVER_PATH/lib/fetch.py $SERVER_PATH/lib/config.ini "daily" $slist
    /usr/bin/python $SERVER_PATH/lib/realtime_analyzer.py $SERVER_PATH/lib/config.ini "daily" $slist

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
