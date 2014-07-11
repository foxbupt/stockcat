#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分析策略的管理框架
#date: 2014/06/27

import sys, re, json, os
import datetime, time, logging, logging.config
import redis
sys.path.append('../../../../server')
from pyutil.util import Util, safestr
from pyutil.sqlutil import SqlUtil, SqlConn
from policy_worker import PolicyWorker
from stock_util import get_stock_list, get_past_openday, get_past_data

class PolicyManager(object):
    instance_map = {}

    def __init__(self, config_info):
        self.config_info = config_info
        self.db_config = config_info['DB']
        self.redis_config = config_info['REDIS']
        self.day = int("{0:%Y%m%d}".format(datetime.date.today()))

    def core(self):
        policy_content = open(self.config_info['POLICY']['config']).read()
        #print policy_content
        worker_config = json.loads(policy_content)
        #print worker_config

        datamap = self.make_datamap()
        queue_policy_map = dict()
        queue_list = []

        for policy_name, policy_config in worker_config.items():
            queue_list.append(policy_config['queue'])
            queue_policy_map[policy_config['queue']] = policy_name

            instance_list = []
            for i in range(policy_config['thread_count']):
                policy_instance = PolicyWorker(policy_name, i, policy_config, self.config_info, datamap)
                policy_instance.start()
                instance_list.append(policy_instance)
                logging.getLogger("policy").debug("desc=policy_worker_start name=%s index=%d id=%s", policy_name, i, str(policy_instance.ident))
            self.instance_map[policy_name] = instance_list

        # TODO: 考虑在主线程中取出队列item, 然后稳定分发到对应instance的queue里
        conn = redis.StrictRedis(self.redis_config['host'], self.redis_config['port'])
        while True:
            try:
                data = conn.blpop(queue_list, 1)
                if data is None:
                    cur_time = datetime.datetime.now().time()
                    cur_timenumber = cur_time.hour * 10000 + cur_time.minute * 100 + cur_time.second
                    #print "policy timenumber=" + str(cur_timenumber)

                    if cur_timenumber >= int(self.config_info['POLICY']['close_time']):
                        logging.getLogger("policy").critical("op=market_closed day=%d time=%d", self.day, cur_timenumber)
                        break
                    else:
                        continue

                key = data[0]
                if key not in queue_policy_map:
                    conn.rpush(key, data[1])
                    continue

                name = queue_policy_map[key]
                item = json.loads(data[1])
                #print item

                sid = item['sid']
                index = sid % len(self.instance_map[name])
                self.instance_map[name][index].enqueue(item)

                logging.getLogger("policy").debug("desc=dispatch_item key=%s name=%s sid=%d index=%d", key, name, sid, index)
            except (KeyboardInterrupt, SystemExit): 
                logging.getLogger("policy").critical("desc=system_exit")
                break

        self.terminate()

    def terminate(self):
        for policy_name, instance_list in self.instance_map.items():
            for instance in instance_list:
                instance.stop()
                instance.join()

        self.instance_map.clear()
        logging.getLogger("policy").critical("op=policy_terminate")
        
    # 构造公共数据
    def make_datamap(self):
        datamap = dict()
        datamap['stock_list'] = stock_list = get_stock_list(self.db_config, 1)

        scode2id_map = dict()
        id2scode_map = dict()
        for sid, stock_info in stock_list.items():
            scode = stock_info['ecode'].lower() + stock_info['code']
            scode2id_map[stock_info['code']] = sid
            id2scode_map[sid] = scode

        datamap['scode2id'] = scode2id_map
        datamap['id2scode'] = id2scode_map
        datamap['past_data'] = get_past_data(self.db_config, self.redis_config, self.day, 5)
        #print len(datamap['past_data'])

        return datamap

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf>"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])
    config_info['DB']['port'] = int(config_info['DB']['port'])
    config_info['REDIS']['port'] = int(config_info['REDIS']['port'])

     # 初始化日志
    logging.config.fileConfig(config_info["LOG"]["conf"])

    manager = PolicyManager(config_info)
    manager.core()
