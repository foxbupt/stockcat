#!/bin/bash
#desc: 运行每日分析
#author: fox
#date: 2013-08-28

main()
{
    location=1
    if [ $# -ge 1 ]
    then
        location=$1
    fi

    day=`get_curday "$location"`
    if [ $# -eq 2 ]
    then
        day=$2
    fi

    result_path=$STOCK_SCRAPY_PATH/data/$day
    log="analyze_${location}_${day}.log"
    echo "location=$location day=$day log=$log"

    # 更新每日的最高价/最低价
    /usr/bin/python /home/fox/web/stockcat/server/lib/daily_refresh.py /home/fox/web/stockcat/server/lib/config.ini $location $day >> $result_path/refresh_${location}_highlow.log 

    # 深入分析
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php analyze $location $day 10 >> $result_path/$log

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
