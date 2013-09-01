#!/bin/bash
#desc: 运行每日分析
#author: fox
#date: 2013-08-28

main()
{
    day=`date "+%Y%m%d"`
    if [ $# -eq 1 ]
    then
        day=$1
    fi

    result_path=$STOCK_SCRAPY_PATH/data/$day
    log="analyze_$day.log"

    # 更新每日的最高价/最低价
    /usr/bin/python /home/fox/web/stockcat/server/lib/daily_refresh.py /home/fox/web/stockcat/server/lib/config.ini $day >> $result_path/$log

    # TODO: 深入分析

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
