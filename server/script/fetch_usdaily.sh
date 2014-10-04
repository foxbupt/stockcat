#!/bin/bash
#desc: 抓取美股每日总览数据
#author: fox
#date: 2014/10/04

main()
{
    stype="stock"
    day=`date -d "1 day ago" "+%Y%m%d"`

    if [ $# -ge 1 ]
    then
        stype=$1
    fi
    if [ $# -ge 2 ]
    then
        day=$2
    fi

    open=`is_market_open "$day" 3`
    echo "stype=$stype day=$day open=$open"
    if [ "$open" == "0" ]
    then
        exit
    fi

    cd $STOCK_SCRAPY_PATH
    result_path=$STOCK_SCRAPY_PATH/data/$day
    if [ ! -d $result_path ]
    then
        mkdir $result_path
    fi

    log="usdaily_${stype}_$day.log"
    filename="usdaily_${stype}_$day.json"
    echo "log=$log filename=$filename"

    # 用scrapy抓取总览信息
    if [ "stock" == $stype ]
    then
        $SCRAPY_BIN crawl qqusdaily -a filename=$US_STOCK_LIST -a request_count=10 -a day=$day -o $result_path/$filename --logfile=$result_path/fetch_usdaily.log >> $result_path/$log
    else
        $SCRAPY_BIN crawl qqusdaily -a filename=$US_INDEX_LIST -a request_count=9 -a day=$day -o $result_path/$filename --logfile=$result_path/fetch_usdaily.log >> $result_path/$log
    fi

    # 导入数据
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php importDaily $result_path/$filename >> $WEB_PATH/import_usdaily.log

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
