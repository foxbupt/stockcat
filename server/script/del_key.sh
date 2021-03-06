#!/bin/bash
#desc: 批量删除多个key
#author: fox
#date: 2014/06/24

main()
{
    if [ $# -lt 1 ]
    then
        echo "Usage: $0 <pattern>"
        echo "Usage: $0 detail*"
    fi

    pattern=$1
    /usr/local/bin/redis-cli keys "$pattern" | while read key
    do
        /usr/local/bin/redis-cli del "$key"
        echo "del $key"
    done
    /usr/local/bin/redis-cli save
}


cd ${0%/*}
. ./comm.inc
main "$@"
