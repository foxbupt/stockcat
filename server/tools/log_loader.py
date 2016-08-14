#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 把日志行解析成dict, 并追加到redis的queue中
#date: 2016/07/02                                

import sys, re, json, os
import datetime, time
#sys.path.append('../../../../server')
#from pyutil.util import safestr, format_log
import redis      

class LogLoader:
    def __init__(self, redis_host, redis_port):   
        self.redis_host = redis_host
        self.redis_port = redis_port
        
    '''
        @desc: 把日志行解析为json对象, 日志行格式为key=value k=v
        @param: line string
        @param: pattern string 匹配正则表达式, 可以为空
        @return dict/None
    '''           
    @staticmethod
    def line2json(line, pattern):
        line = line.strip("\n ")
        if len(line) == 0:
            return None
        
        if len(pattern) > 0:
            match_content = re.search(r'' + pattern + ' (.*)', line)
            if not match_content:
                return None
            data = match_content.group(1)
            #print data
        else:
            data = line     
        
        item = dict()
        
        #由于json中含有空格, 只能逐步解析    
        index = 0   
        while index < len(data): 
            offset = data.find("=", index)
            if offset == -1:
                break
            
            key = data[index:offset]
            value_offset = data.find("=", offset+1)  
            # 表明是最后1段
            if value_offset == -1:
                value_offset = len(data[offset+1:]) 
                value = data[offset+1:]
                index = len(data)
            else:
                last_sep_index = data.rfind(" ", offset+1, value_offset)
                if last_sep_index == -1:
                    value = data[offset+1:value_offset]
                else:
                    value = data[offset+1:last_sep_index]
                    index = last_sep_index +1
            
            #print key, value
            value = value.replace("u'", "'").replace("'", "\"").replace("(", "[").replace(")", "]")
            valueobj = None
                   
            try:
                # json对象 和json数组 
                if value.startswith("{") or value.startswith("["):
                    valueobj = json.loads(value, encoding="utf-8")
                elif re.match(r'[+-]?\d+(\.\d+)?$', value):
                    if value.find(".") != -1:
                        valueobj = float(value)
                    else:
                        valueobj = int(value)
                elif value.upper() == "TRUE":
                    valueobj = True
                elif value.upper() == "FALSE":
                    valueobj = False
                else:
                    valueobj = value
            except Exception as e:
                print e
                print "err=invalid_line key=" + key + " value=" + value
                continue
            
            if valueobj:
                item[key] = valueobj   
        #print item                 
        return item
    
    '''
        @desc: 解析日志文件内容, 把匹配日志内容转化为json追加到redis的queue中  
        @param queue string
        @param filename string
        @param pattern string
        @return count 记录条数
    '''                       
    def loadfile(self, queue, filename, pattern): 
        conn = redis.StrictRedis(self.redis_host, self.redis_port)        
        count = 0
        
        try:
            content = open(filename).read()
            lines = content.split("\n")
            for line in lines:
                item = LogLoader.line2json(line, pattern)
                if item is None:
                    continue    
                
                count += 1
                conn.rpush(queue, json.dumps(item))
                if count % 10 == 0:
                    time.sleep(2)
        except Exception as e:
            print "err=loadfile filename=" + filename, " pattern=" + pattern + " queue=" + queue  
            return 
        
        print "desc=load_stat filename=" + filename, " pattern=" + pattern + " queue=" + queue + " count=" + str(count)   
                    
if __name__ == "__main__":
    '''
    line="2016-07-01 22:14:25 MainThread - 140208109909760 realtime_policy.py[line:63] INFO op=realtime_chance daily_item={u'high_price': 7.7000000000000002, u'code': u'FMSA', u'vary_price': -0.19, u'name': u'FMSA', u'predict_volume': 1138.0, u'low_price': 7.5, u'vary_portion': -2.46, u'exchange_portion': 0.0012476780185758515, u'volume': 2015, u'amount': 0, u'last_close_price': 7.71, u'open_price': 7.6299999999999999, u'time': u'213128', u'swing': 0.25940337224383941, u'sid': 6391, u'close_price': 7.5199999999999996, u'cap': 1214480000.0, u'day': 20160701, u'out_capital': 161500000.0} code=FMSA trend_item={'high_price': 7.8300000000000001, 'direction': -1, 'end': 44, 'trend': 1, 'low_price': 7.7000000000000002, 'start': 34, 'length': 11, 'vary_portion': -1.6602809706257968} sid=6391 chance={'price_range': (7.7000000000000002, 7.7800000000000002), 'stop_price': 7.8700000000000001, 'op': 2} daily_trend=2 time=1014"

    item = LogLoader.line2json(line, "op=realtime_chance")
    print item
    '''

    if len(sys.argv) < 4:
        print "Usage: " + sys.argv[0] + " <redis_ip:port> <queue> <filename> [pattern]"
        sys.exit(1)

    (redis_ip, redis_port) = sys.argv[1].split(":")
    redis_port = int(redis_port)


    loader = LogLoader(redis_ip, redis_port)        
    pattern = ""
    if len(sys.argv) >= 5:
        pattern = sys.argv[4]
    loader.loadfile(sys.argv[2], sys.argv[3], pattern)
