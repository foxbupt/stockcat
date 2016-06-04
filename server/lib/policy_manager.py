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
from stock_util import get_stock_list, get_past_openday, get_past_data, get_scode

class PolicyManager(object):
    instance_map = {}

    def __init__(self, config_info):
        self.config_info = config_info
        self.db_config = config_info['DB']
        self.redis_config = config_info['REDIS']

    def core(self, location):
        self.location = location
        self.day = int("{0:%Y%m%d}".format(datetime.date.today()))
        if location == 3:
            self.day = int("{0:%Y%m%d}".format(datetime.date.today() - datetime.timedelta(days = 1)))
            
        policy_content = open(self.config_info['POLICY']['config']).read()
        #print policy_content
        
        worker_config = json.loads(policy_content)
        #print worker_config

        datamap = self.make_datamap()
        instance_list = []

        for policy_name, policy_config in worker_config.items():
            for i in range(policy_config['process_count']):
                policy_instance = PolicyWorker(policy_name, policy_config, self.config_info, datamap)
                policy_instance.start()

                instance_list.append(policy_instance)
                logging.getLogger("policy").debug("desc=policy_worker_start name=%s index=%d id=%s", policy_name, i, str(policy_instance.get_pid()))

        for instance in instance_list:
            instance.join()
        logging.getLogger("policy").critical("op=policy_manager_exit")

    def terminate(self):
        for instance in self.instance_list:
            instance.stop()
            instance.join()

        self.instance_list.clear()
        logging.getLogger("policy").critical("op=policy_terminate")
        
    # 构造公共数据
    def make_datamap(self):
        datamap = dict()
        datamap['stock_list'] = stock_list = get_stock_list(self.db_config, 1)

        scode2id_map = dict()
        id2scode_map = dict()
        for sid, stock_info in stock_list.items():
            scode = get_scode(stock_info['code'], int(stock_info['ecode']), int(stock_info['location']))
            scode2id_map[stock_info['code']] = sid
            id2scode_map[sid] = scode

        datamap['scode2id'] = scode2id_map
        datamap['id2scode'] = id2scode_map
        datamap['past_data'] = get_past_data(self.db_config, self.redis_config, self.day, 5)
        print len(datamap['past_data'])

        return datamap

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf> [location]"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])
    config_info['DB']['port'] = int(config_info['DB']['port'])
    config_info['REDIS']['port'] = int(config_info['REDIS']['port'])

     # 初始化日志
    logging.config.fileConfig(config_info["LOG"]["conf"])

    location = 1
    if len(sys.argv) >= 3:
        location = int(sys.argv[2])
        
    manager = PolicyManager(config_info, location)
    manager.core()
