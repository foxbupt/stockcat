#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: convert name to pinyin
#date: 2014/10/09

import datetime, sys, time, json
sys.path.append('../../../../server')  
from pyutil.util import Util, safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
from stock_util import get_stock_list
from pypinyin import pinyin

def name2pinyin(name):
    input = name.decode('utf-8')

    letter_list = pinyin(input, 4)
    #print letter_list
    output = "".join([ x[0] for x in letter_list])
    output = safestr(output)
    #print safestr(name), safestr(input), output

    return output

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf>"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])        
    db_config = config_info['DB']
    db_config['port'] = int(db_config['port'])

    stock_list = get_stock_list(db_config)
    for sid, stock_info in stock_list.items():
        pinyin_code = name2pinyin(stock_info['name'])
        sql = "update t_stock set pinyin='" + pinyin_code + "' where id=" + str(sid) + ";"
        print sql


