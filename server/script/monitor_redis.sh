#!/bin/bash
#desc: monitor redis-server
#author: fox
#date: 2014/10/09

REDIS_SERVER=/usr/local/bin/redis-server

main()
{
    pidlist=`ps aux | grep "redis-server" | grep -v "grep" | awk '{print $2}'`
    if [ -z "$pidlist" ]
    then
        echo "redis-server has gone, need start"
        $REDIS_SERVER /etc/redis/6379.conf
        echo "start redis-server"
    else
        echo "redis-server is running, pid=$pidlist"
    fi

}

cd ${0%/*}
. ./comm.inc
main "$@"
