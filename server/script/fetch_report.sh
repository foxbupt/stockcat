#!/bin/bash
#desc: 每天定时拉取研报
#author: fox
#date: 2013/08/12

main()
{
    day=`date "+%Y%m%d"`
    if [ $# -eq 1 ]
    then
        day=$1
    fi
    echo "day = $day"

    cd $STOCK_SCRAPY_PATH
    result_path=$STOCK_SCRAPY_PATH/data/$day
    if [ ! -d $result_path ]
    then
        mkdir $result_path
    fi

    filename="report_$day.json"
    log="report_$day.log"

    if [ -f $result_path/$filename ]
    then
        rm -f $result_path/$filename
    fi

    # 用scrapy抓取研报
    $SCRAPY_BIN crawl hexun -a day=$day -o $result_path/$filename >> $result_path/$log
    echo "day=$day filename=$filename log=$log"

    # 导入研报
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php importReport $result_path/$filename >> $WEB_PATH/import_report.log 
}

cd ${0%/*}
. ./comm.inc
main "$@"
