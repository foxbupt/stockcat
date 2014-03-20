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
    /usr/bin/python /home/fox/web/stockcat/server/lib/daily_refresh.py /home/fox/web/stockcat/server/lib/config.ini $day >> $result_path/refresh_highlow.log 

    # 深入分析
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php analyze $day 5 >> $result_path/$log

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
