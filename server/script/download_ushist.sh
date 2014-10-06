#!/bin/bash
#desc: 从Yahoo抓取美股历史数据
#author: fox
#date: 2014/10/04

sub_month()
{
    month=$1
    result=0

    one=`echo $month | awk '{print substr($1, 1, 1)}'`
    two=`echo $month | awk '{print substr($1, 2, 1)}'`

    if [ $one -eq 0 ]
    then
        result=`expr $two - 1`
    else
        result=`expr $month - 1`
    fi

    echo "$result"
}

main()
{
    if [ $# -lt 4 ]
    then
        echo "Usage: $0 <path> <stock_list> <start> <end>"
        exit
    fi

    path=$1
    filename=$2
    start=$3
    end=$4

    start_year=`echo $start | awk '{print substr($1, 1, 4)}'`
    start_month=`echo $start | awk '{print substr($1, 5, 2)}'`
    start_sub_month=`sub_month "$start_month"`
    start_day=`echo $start | awk '{print substr($1, 7, 2)}'`

    end_year=`echo $end | awk '{print substr($1, 1, 4)}'`
    end_month=`echo $end | awk '{print substr($1, 5, 2)}'`
    end_sub_month=`sub_month "$end_month"`
    end_day=`echo $end | awk '{print substr($1, 7, 2)}'`

    while read line
    do
        sid=`echo "$line" | awk '{print $1}'`
        code=`echo "$line" | awk '{print $2}' | sed "s/us//g"`
        url="http://real-chart.finance.yahoo.com/table.csv?s=${code}&a=${start_sub_month}&b=${start_day}&c=${start_year}&d=${end_sub_month}&e=${end_day}&f=${end_year}&g=d&ignore=.csv "
        #echo "$url"

        data_filename="${path}/${code}.csv"
        wget "$url" -O ${data_filename}

        $PHP_BIN -c /etc/php.ini $WEB_PATH/console_entry.php importushist $sid ${data_filename} >> $WEB_PATH/import_ushist.log
        echo "op=import_ushist sid=$sid code=$code filename=$data_filename"
    done < $filename

    echo "finish"
}


cd ${0%/*}
. ./comm.inc
main "$@"
