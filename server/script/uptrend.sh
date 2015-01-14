#!/bin/bash
#desc: 运行突破趋势位的股票列表
#author: fox
#date: 2015/1/14

main()
{
    if [ $# -lt 1 ]
    then
        echo "Usage: $0 <location> [type]"
        exit
    fi

    location=$1
    day=`get_curday "$location"`

    echo "loation=$location day=$day"
    result_path=$STOCK_SCRAPY_PATH/data/$day
    log="uptrend_${location}_${day}.log"

    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php top $location >> $result_path/$log

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
