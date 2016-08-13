#!/bin/bash
#desc: 公共函数
#author: fox
#date: 2013-08-12

STOCKCAT_PATH=/home/fox/web/stockcat
STOCK_SCRAPY_PATH=$STOCKCAT_PATH/spider/stock
WEB_PATH=$STOCKCAT_PATH/web
SERVER_PATH=$STOCKCAT_PATH/server
STOCK_LIST=$STOCKCAT_PATH/web/data/stock_list.txt
INDEX_LIST=$STOCKCAT_PATH/web/data/index_list.txt
US_STOCK_LIST=$STOCKCAT_PATH/web/data/us_stock_list.txt
PHP_BIN=/usr/bin/php
SCRAPY_BIN=/usr/bin/scrapy

# 公共假期
HOLIDAYS=(20160101 20160208 20160209 20160210 20160211 20160212 20160404 20160502 20160609 20160610 20160915 20160916 20161003 20161004 20161005 20161006 20161007)
US_HOLIDAYS=(20160216 20160403 20160525 20160703 20160907 20161126 20161225)

#desc: 判断当天是否开市
#param: day 日期
#param: location 国家
#return 1/0
is_market_open()
{
    day=$1
    # 缺省为大陆
    location=1

    if [ $# -eq 2 ]
    then
        location=$2
    fi

    weekday=`date -d "$day" "+%w"`
    if [ 1 -eq $location ] 
    then
        holidays=(`echo ${HOLIDAYS[@]}`)
    else
        holidays=(`echo ${US_HOLIDAYS[@]}`)
    fi
    #echo "day=$day location=$location holiday_count=${#holidays[*]} holidays=${holidays[@]}"

    if [ 0 -eq $weekday -o 6 -eq $weekday ]
    then
        echo "0"
    else 
        for loop_day in ${holidays[@]}
        do
            if [ $loop_day == $day ] 
            then
                echo "0"
                return
            fi
        done

        echo "1"
    fi
}

#desc: 根据location获取当前交易日
#param: location 国家
#return day
get_curday()
{
    location=$1

    if [ 1 -eq $location -o 2 -eq $location ]
    then
        day=`date "+%Y%m%d"`
    else
        day=`date -d "12 hour ago" "+%Y%m%d"`
    fi

    echo "$day"
}
