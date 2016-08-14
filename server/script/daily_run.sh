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

	open=`is_market_open "$day"`
    echo "day=$day open=$open"
    if [ "$open" == "0" ]
    then
        exit
    fi

    result_path=$STOCK_SCRAPY_PATH/data/$day
    log="analyze_${location}_${day}.log"
    echo "location=$location day=$day log=$log"

    cd /home/fox/web/stockcat/server/lib/
    # 更新每日的最高价/最低价, 记录价格突破的股票
    /usr/bin/python ./daily_refresh.py /home/fox/web/stockcat/server/lib/config.ini $location $day >> $result_path/refresh_${location}_highlow.log 

    # 分析出连续上涨的股票
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php analyze $location $day 10 >> $result_path/$log

    # 每天更新突破趋势阻力位的股票列表
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php top $location >> $result_path/uptrend_${location}_${day}.log

    # 更新rank
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php rank ${day} $location >> $result_path/rank_${location}.log

    # 分析蜡烛形态
    $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php candle $location $day >> $result_path/candle_${location}.log
    
    # 更新动态信息
    /usr/bin/python ./offline_handler.py /home/fox/web/stockcat/server/lib/config.ini $location $day >> $result_path/dyn_${location}.log 2>1
    
    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
