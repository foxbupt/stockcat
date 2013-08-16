#!/bin/bash
#desc: 抓取股票重大事件
#author: fox
#date: 2013-08-12

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
    log="event_$day.log"
    filename="event_$day.json"

    while read line
    do
        code=`echo $line | awk '{print substr($2, 3)}'`
        echo "op=fetch_event code=$code day=$day" >> $result_path/$log

        # 用scrapy抓取股票公告
        $SCRAPY_BIN crawl event -a code=$code -a interval=10 -a day=$day -o $result_path/$filename >> $result_path/$log

        sleep 1
    done < $STOCK_LIST

    # 导入数据
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php importEvent $result_path/$filename >> $WEB_PATH/import_event.log
    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
