#!/bin/bash
#desc: fetch us stock list from qq
#author: fox
#date: 2014/09/29

while read line
do
    cat=`echo $line | awk '{print $1}'`
    count=`echo $line | awk '{print $2}'`
    echo "cat=$cat page_count=$count"

    /usr/bin/scrapy crawl "qqus" -a url="http://stockapp.finance.qq.com/mstats/#mod=list&id=us_imp&module=US&type=IMP" -a category="$cat" -a page_count="$count" -o $cat.json --logfile=fetch_qqus.log
    echo "fetch_done cat=$cat"
done < qqus.txt

echo "finish"
