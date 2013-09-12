#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 分析器公共接口
#date: 2013-10-23

import sys, string
sys.path.append('../../../../server')
from pyutil.util import safestr
from pyutil.sqlutil import SqlUtil, SqlConn

class PolicyUtil:
    '''
        @desc: 加载分析器
        @param: db_config dict
        @param: pid int
        @return dict{'policy', 'items', 'vars', ''}
    '''
    @staticmethod
    def load_policy(db_config, pid):
        policy_info = dict()

        policy_record = PolicyUtil.get_policy_info(db_config, pid)
        if policy_record is None:
            return None

        policy_info['policy'] = policy_record
        policy_info['items'] = dict()
        policy_info['vars'] = set()

        policy_item_list = PolicyUtil.get_policy_itemlist(db_config, pid)
        if not policy_item_list:
            return policy_info

        # 把条件项序列化为dict的条件项
        nodes = dict()
        root_id = int(policy_record['root_item'])

        # 存储分析器中所有的条件变量
        for item_info in policy_item_list:
            item_id = int(item_info['id'])
            policy_info['items'][item_id] = item_info
            policy_info['vars'].add(item_id)

            parent_id = int(item_info['parent_id'])
            if item_id == root_id:
                continue
            else:
                if parent_id not in nodes:
                    nodes[parent_id] = list()
                nodes[parent_id].append(item_id)

        policy_info['condition'] = PolicyUtil.expand_itemnode(root_id, nodes, policy_info['items'])
        return policy_info

    '''
        @desc: 获取分析器详细信息
        @param: db_config dict
        @param pid int
        @return dict
    '''
    @staticmethod
    def get_policy_info(db_config, pid):
        sql = "select id, type, name, remark, uid, root_item from t_policy where id={pid} and status = 'Y'".format(pid=pid)
        print sql

        try:
            db_conn = SqlUtil.get_db(db_config)
            record_list = db_conn.query_sql(sql)
        except Exception as e:
            print e
            return None

        if len(record_list) < 1:
            return None

        return record_list[0]

    '''
        @desc: 获取分析器的条件项列表
        @param: db_config dict
        @param: pid int
        @return list
    '''
    @staticmethod
    def get_policy_itemlist(db_config, pid):
        sql = "select id, name, vid, optor, param, value, pid, parent_id, node_type, logic from t_policy_item where pid = {pid} and status = 'Y'".format(pid = pid)
        print sql

        try:
            db_conn = SqlUtil.get_db(db_config)
            record_list = db_conn.query_sql(sql)
        except Exception as e:
            print e
            return None

        return record_list

    '''
        @desc: 获取分析器变量列表
        @param: db_config dict
        @param: vid int
    '''
    @staticmethod
    def get_varlist(db_config, vid = 0):
        sql = "select id, code, name, type, expression, add_time from t_policy_var where status = 'Y'"
        #print sql

        try:
            db_conn = SqlUtil.get_db(db_config)
            record_list = db_conn.query_sql(sql)
        except Exception as e:
            print e
            return None

        var_list = dict()
        for record in record_list:
            var_list[int(record['id'])] = record

        if vid > 0:
            return var_list[vid] if vid in var_list else None

        return var_list

    '''
        @desc: 递归展开某个节点下的所有条件项
        @param: node_id int
        @param: nodes dict
        @param: items dict
        @return dict('item_id', 'logic', 'children')
    '''
    @staticmethod
    def expand_itemnode(node_id, nodes, items):
        node_data = items[node_id]
        data = {'item_id':node_id, 'logic': node_data['logic']}
        data['children'] = []

        if node_id in nodes:
            for child_node_id in nodes[node_id]:
                child_node_info = items[child_node_id]
                if 1 == child_node_info['node_type']:
                    data['children'].append(child_node_id)
                else:
                    data['children'].append(PolicyUtil.expand_itemnode(child_node_id, nodes, items))

        return data

    '''
        @desc: 判断单个条件项的逻辑结果
        @param: day int 指定日期
        @param: item_info dict
        @param: var_list dict
        @param: data_map dict
        @param: stock_var list
        @return bool
    '''
    @staticmethod
    def check_item(day, item_info, var_list, data_map, stock_var):
        #print item_info
        vid = int(item_info['vid'])
        optor = int(item_info['optor'])
        param = item_info['param']

        item_var_info = var_list[vid]
        vcode = item_var_info['code'].lower()
        item_type = int(item_var_info['type'])

        result = False
        if item_type == 1:
            if vcode in data_map:
                stock_value = data_map[vcode]
                result = PolicyUtil.evaluta_expression(stock_value, optor, item_info['value'])
                print vid, vcode, param, stock_value, optor, item_info['value'], result
            else:
                print "1111"

        elif 4 == item_type:    # 带参数的变量, 需要通过stock_var来匹配, value对应sum, param对应cont
            for record in stock_var:
                if int(record['vid']) == vid: # 暂时只考虑param为简单数值的情况
                    result = PolicyUtil.evaluta_expression(record['sum'], optor, item_info['value'])
                    if result and item_info['param']:
                        result = PolicyUtil.evaluta_expression(record['cont'], optor, item_info['param'])
                    break

        return result

    '''
        @desc: 计算表达式的值
        @param: lval mixed
        @param: optor int
        @param: rval mixed
        @return bool
    '''
    @staticmethod
    def evaluta_expression(lval, optor, rval):
        try:
            if 1 == optor: # ==
                return float(lval) == float(rval)
            elif 2 == optor: # >=
                return float(lval) >= float(rval)
            elif 3 == optor: # >
                return float(lval) > float(rval)
            elif 4 == optor: # <
                return float(lval) < float(rval)
            elif 5 == optor: # <=
                return float(lval) <= float(rval)
            elif 6 == optor: # !=
                return float(lval) != float(rval)
            else: # 字符串操作符
                lval_str = str(lval).strip()
                rval_str = str(rval).strip()

                index = lval_str.find(rval_str)
                if 7 == optor:
                    return index != -1
                elif 8 == optor:
                    return index == -1

                field_list = lval_str.split(",")
                contained = True if rval_str in field_list else False
                if 9 == optor:
                    return contained
                else:
                    return not contained
        except Exception as e:
            return False

        return False
