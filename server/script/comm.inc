#!/bin/bash
#desc: 公共函数
#author: fox
#date: 2013-08-12

STOCKCAT_PATH=/home/fox/web/stockcat
STOCK_SCRAPY_PATH=$STOCKCAT_PATH/spider/stock
WEB_PATH=$STOCKCAT_PATH/web
STOCK_LIST=$STOCKCAT_PATH/web/data/stock_list.txt
INDEX_LIST=$STOCKCAT_PATH/web/data/index_list.txt
PHP_BIN=/usr/bin/php
SCRAPY_BIN=/usr/bin/scrapy

# 公共假期
HOLIDAYS=(20130919, 20130920, 20130921, 20131001, 20131002, 20131003, 20131004, 20131005, 20131006, 20131007)

# 判断当天是否开市
is_market_open()
{
    day=$1
    weekday=`date -d "$day" "+%w"`
    if [ 0 -eq $weekday -o 6 -eq $weekday ]
    then
        echo "0"
    else 
        for loop_day in ${HOLIDAYS[@]}
        do
            if [ $loop_day -eq $day ] 
            then
                echo "0"
            fi
        done
    fi

    echo "1"
}
