#!/bin/bash
#desc: 更新股票历史数据
#author: fox
#date: 2013-08-12

main()
{
    if [ $# -lt 1 ]
    then
        echo "Usage: refresh_hist.sh <hist|year|month6|month3|> [sid]"
        exit
    fi

    type=$1

    sid=0
    if [ $# -eq 2 ]
    then
        sid=$2
    fi
    echo "sid = $sid, type = $type"

    sql="select sid, max(close_price), min(close_price) from t_stock_data where status = 'Y'"
    #sql="select sid, max(high_price), min(low_price) from t_stock_data where status = 'Y'"
    if [ "year" == "$type" ]
    then
        year_date=`date "+%Y0101"`
        sql="$sql and day >= $year_date"
    elif [ "month6" == "$type" ]
    then
        start_day=`date "+%Y%m%d" -d "60 day ago"`
        sql="$sql and day >= $start_day"
    elif [ "month3" == "$type" ]
    then
        start_day=`date "+%Y%m%d" -d "30 day ago"`
        sql="$sql and day >= $start_day"
    fi

    if [ $sid -gt 0 ]
    then
        sql="$sql and sid = $sid;"
    else 
        sql="$sql group by sid;"
    fi
    echo "sql = $sql"

    mysql -uwork -pslanissue -Ddb_stockcat --skip-column -e "$sql" >> $type.txt
    field_high="${type}_high"
    field_low="${type}_low"

    cat $type.txt | while read line
    do
        sid=`echo $line | awk '{print $1}'`
        high_value=`echo $line | awk '{print $2}'`
        low_value=`echo $line | awk '{print $3}'`

        echo "update t_stock set $field_high = $high_value, $field_low = $low_value where id = $sid;" >> $type.sql
    done

    mysql -uwork -pslanissue -Ddb_stockcat < $type.sql
}

cd ${0%/*}
. ./comm.inc
main "$@"
