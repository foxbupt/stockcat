#!/bin/bash
#desc: 抓取股票历史每日总览数据
#author: fox
#date: 2013-12-08

main()
{
    if [ $# -lt 2 ]
    then
        echo "Usage: $0 <start> <end>"
        exit
    fi

    start=$1
    end=$2

    cd $STOCK_SCRAPY_PATH
    result_path=$STOCK_SCRAPY_PATH/data/hist/

    while read line
    do
        sid=`echo $line | awk '{print $1}'`
        code=`echo $line | awk '{print $2}'`
        filename="${code}_hist_${start}_${end}.json"
        echo "sid=$sid code=$code filename=$filename"
        $SCRAPY_BIN crawl ifeng -a id=$sid -a code=$code -a start_date=$start -a end_date=$end -o $result_path/$filename --logfile=$result_path/fetch_histdat.log 
    done < $STOCK_LIST

    while read line
    do
        sid=`echo $line | awk '{print $1}'`
        code=`echo $line | awk '{print $2}'`
        filename="${code}_hist_${start}_${end}.json"
        echo "sid=$sid code=$code filename=$filename"
        $SCRAPY_BIN crawl ifeng -a id=$sid -a code=$code -a start_date=$start -a end_date=$end -o $result_path/$filename --logfile=$result_path/fetch_histdat.log 
    done < $INDEX_LIST

    # 导入数据
    #$PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php importStockHist $result_path/$filename >> $WEB_PATH/import_hist.log

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
