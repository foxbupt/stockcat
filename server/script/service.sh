#!/bin/bash
#desc: 服务的控制脚本
#author: fox
#date: 2014/07/09

PYTHON_BIN=/usr/bin/python
SERVICE_PATH=/home/fox/web/stockcat/server/lib

main()
{
    if [ $# -lt 1 ]
    then
        echo "Usage: $0 <cmd> [service] [location] [day] "
        echo "cmd: start|stop|restart|view"
        echo "service: scheduler|policy|all"
    fi

    cmd=$1
    service="all"
    if [ $# -ge 2 ]
    then
        service=$2
    fi

    shift 2
    
    if [ "$cmd" == "start" ]
    then
        if [ "$service" == "scheduler" ]
        then
            start_scheduler "$@" 
        elif [ "$service" == "policy" ]
        then
            start_policy "$@"
        elif [ "$service" == "all" ]
        then
            start_scheduler "$@"
            start_policy "$@" 
        fi
    elif [ "$cmd" == "stop" ]
    then
        if [ "$service" == "scheduler" ]
        then
            stop_scheduler
        elif [ "$service" == "policy" ]
        then
            stop_policy
        elif [ "$service" == "all" ]
        then
            stop_scheduler
            stop_policy
        fi

    elif [ "$cmd" == "restart" ]
    then
        if [ "$service" == "scheduler" ]
        then
            stop_scheduler
            start_scheduler "$@"
        elif [ "$service" == "policy" ]
        then
            stop_policy
            start_policy "$@" 
        elif [ "$service" == "all" ]
        then
            stop_scheduler
            stop_policy
            start_scheduler "$@"
            start_policy "$@" 
        fi
    else
        ps aux | grep scheduler | grep -v "grep"
        ps aux | grep policy | grep -v "grep"

    fi

    echo "finish"
}

start_scheduler()
{
    cd $SERVICE_PATH
    nohup $PYTHON_BIN scheduler.py fetch.ini "$@" >> run.log 2>&1 &
}

start_policy()
{
    cd $SERVICE_PATH
    nohup $PYTHON_BIN policy_manager.py fetch.ini "$@" >> run.log 2>&1 &
}

stop_scheduler()
{
    pidlist=`ps aux | grep "scheduler" | grep -v "grep" | grep "python" | awk '{print $2}'`
    if [ -z "$pidlist" ]
    then
        echo "no scheduler process"
    else
        echo "$pidlist" | while read pid
        do
            kill -9 "$pid"
        done
    fi
}

stop_policy()
{
    pidlist=`ps aux | grep "policy_manager" | grep -v "grep" | awk '{print $2}'`
    if [ -z "$pidlist" ]
    then
        echo "no policy process"
    else
        echo "$pidlist" | while read pid
        do
            kill -9 "$pid"
        done
    fi
}

restart_scheduler()
{
    stop_scheduler
    start_scheduler "$@"
}

restart_policy()
{
    stop_policy
    start_policy "$@"
}

cd ${0%/*}
. ./comm.inc
main "$@"
