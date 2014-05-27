#!/bin/bash
#desc: 运行股票趋势分析
#author: fox
#date: 2014-05-26

main()
{
    range="all"
    if [ $# -eq 1 ]
    then
        range=$1
    fi

    start_day=`date -d '-3 month' +%Y%m%d`
    day=`date "+%Y%m%d"`
    if [ $# -eq 2 ]
    then
        day=$2
    fi

    echo "day=$day range=$range"
    result_path=$STOCK_SCRAPY_PATH/data/$day
    log="trend_$day.log"

    # 更新所有股票
    if [ $range == "all" ]
    then
        $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php trend $start_day $day 1 >> $result_path/$log
    elif [ $range == "pool" ]
    then
        pool_filename="pool_$day.txt"
        from_day=`date -d '-10 day' +%Y%m%d`

        /usr/bin/mysql -uwork -pslanissue -Ddb_stockcat --skip-column -e "select distinct sid from t_stock_cont where day >= $from_day and day <= $day and status = 'Y';" >> $result_path/$pool_filename
        /usr/bin/mysql -uwork -pslanissue -Ddb_stockcat --skip-column -e "select distinct sid from t_stock_price_threshold where day >= $from_day and day <= $day and status = 'Y';" >> $result_path/$pool_filename

        sort $result_path/$pool_filename | uniq | while read sid
        do
            #echo "desc=refresh_pool_trend sid=$sid"
            $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php trend $start_day $day 1 "$sid" >> $result_path/$log
        done
    fi

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
