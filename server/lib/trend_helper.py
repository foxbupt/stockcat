#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 通用趋势分析
#date: 2016/08/20

import sys, json, os, traceback
from makedata import loaddata

class TrendHelper(object):
    # 趋势定义: 1 下跌 2 震荡 3 上涨
    TREND_FALL = 1
    TREND_WAVE = 2
    TREND_RISE = 3

    # 方向定义: 1 上涨 -1 下跌
    DIRECTION_UP = 1
    DIRECTION_DOWN = -1

    '''
        @desc 从指定位置往前找到相邻同方向的趋势节点
        @param trend_list [{}, ...]
        @param pos int 指定位置
        @param trend int 指定趋势
        @return trend_item_list/None
    '''
    @staticmethod
    def rfind_same_trend(trend_list, pos, trend):
        if pos >= len(trend_list):
            return []

        item = trend_list[pos]
        same_item_list = []
        offset = pos - 1

        while offset >= 0:
            if trend_list[offset]['trend'] == trend:
                same_item = trend_list[offset]
                same_item_list.append(same_item)
            offset -= 1

        return same_item_list

    '''
        @desc 根据两个价格输出趋势判断
        @param pre_price 时间在前的价格
        @param now_price 时间在后的价格
        @param vary_portion 涨跌幅, 缺省为1.0
        @param vary_type 趋势判断的类型, 缺省为portion(幅度), 对于指数取值为value(差值)
        @return trend, 参见TREND_XXX
    '''
    @staticmethod
    def get_trend_by_price(pre_price, now_price, vary_portion=1.0, vary_type = "portion"):
        portion = abs(now_price - pre_price) / pre_price * 100
        diff = abs(now_price - pre_price)
        vary_value = portion if "portion" == vary_type else diff

        if vary_value < vary_portion:
            return TrendHelper.TREND_WAVE
        else:
            return TrendHelper.TREND_RISE if now_price > pre_price else TrendHelper.TREND_FALL

    '''
        @desc 对价格列表分割成多段同一方向的子列表
        @param price_list [price, ...]
        @return list[(start, end, direction, length, high_price, low_price)]
    '''
    @staticmethod
    def partition(price_list):
        delta_list = []
        last_price = price_list[0]
        for price in price_list:
            delta_list.append(price - last_price)
            last_price = price
        #print price_list
        #print delta_list

        range_list = []
        start_index = 0
        end_index = 2

        # 根据阶段高点/低点分割, 这里没有考虑涨跌幅
        direction = TrendHelper.DIRECTION_UP if delta_list[1] >= 0.0 else TrendHelper.DIRECTION_DOWN
        while end_index < len(delta_list):
            if delta_list[end_index] * direction < 0:
                range_item = dict()
                range_item['start'] = start_index
                range_item['end'] = end_index - 1
                range_item['direction'] = direction
                range_item['length'] = end_index - start_index
                range_item['high_price'] = max(price_list[start_index:end_index])
                range_item['low_price'] = min(price_list[start_index:end_index])

                range_list.append(range_item)
                start_index = end_index - 1
                direction = -1 * direction
            end_index += 1

        # 最后一段
        range_item = dict()
        range_item['start'] = start_index
        range_item['end'] = len(delta_list) - 1
        range_item['direction'] = direction
        range_item['length'] = len(delta_list) - start_index
        range_item['high_price'] = max(price_list[start_index:len(delta_list)])
        range_item['low_price'] = min(price_list[start_index:len(delta_list)])
        range_list.append(range_item)
        #print range_list
        return range_list

    '''
        TODO: 由于请求间隔的原因, 可能存在前后2段趋势相同, 需要优先合并
        TODO: 对于上次合并分析的结果需要缓存，下次从最后2段趋势开始合并
        @desc 对分段的趋势节点进行归并处理,
        @param price_list []
        @param range_list [(start, end, direction, length, high_price, low_price)]
        @param trend_config dict
        @return list[{start, end, direction, length, high_price, low_price}]
    '''
    @staticmethod
    def combine_list(price_list, range_list, trend_config):
        item_list = TrendHelper.merge_same_trend(price_list, range_list, trend_config, {'direction': True, "length": True})
        result_list = []
        i = 0

        while i < len(item_list):
            item = item_list[i]
            # 由于往前合并, 上1个节点可能已被合并, 所以取结果中的最后1段节点
            last_item = result_list[-1] if len(result_list) > 0 else None
            next_item = item_list[i + 1] if i < len(item_list) - 1 else None
            need_append = False

            # 趋势节点个数>= 3 或者是收尾节点, 直接追加
            if item['length'] >= 3 or i == 0 or i == len(item_list) - 1:
                need_append = True
            # 当前趋势节点个数 < 3 且下段趋势节点>=3
            elif item['length'] < 3 and next_item and next_item['length'] >= 3:
                '''
                    前后相邻两段趋势肯定相同, 满足以下条件之一则合并:
                    后段长度 >= 3
                    相邻两段趋势都是上升, 且后一段的最高点 >= 前一段最高点
                    相邻两段趋势都是下跌, 且后一段的最低点 <= 前一段的最低点
             '''
                # 前后2段趋势相同, 且后段趋势高点更高、低点更低, 则这3段趋势直接合并
                if (next_item['direction'] == TrendHelper.DIRECTION_UP and next_item['high_price'] >= last_item['high_price']) or (next_item['direction'] == TrendHelper.DIRECTION_DOWN and next_item['low_price'] <= last_item['low_price']):
                    trend_item = TrendHelper.extend_trend(price_list, last_item, next_item, trend_config, "end")
                    result_list[-1] = trend_item
                    need_append = False
                    i = i + 2
                else:
                    need_append = True
            else:
                need_append = True

            if need_append:
                result_list.append(item)
                i += 1

        return TrendHelper.merge_same_trend(price_list, result_list, trend_config, {})

        '''
        print item_list
        result_list = []
        current_item = None
        i = 0
        while i < len(item_list):
            item = item_list[i]

            # 由于往前合并, 上1个节点可能已被合并, 所以取结果中的最后1段节点
            next_item = item_list[i + 1] if i < len(item_list) - 1 else None
            count = item["length"]
            need_append = False

            if current_item is None:
                current_item = item
            elif current_item['trend'] == item['trend']:
                current_item = TrendHelper.extend_trend(price_list, current_item, item, trend_config, "end")
            elif item['trend'] == TrendHelper.TREND_WAVE:
                # 中间为震荡节点, 前后2段趋势相同方向 且 突破新高或新低时, 合并震荡前后的趋势
                if next_item and next_item['trend'] == current_item['trend'] and ((current_item['direction'] == TrendHelper.DIRECTION_UP and price_list[next_item['end']] >= price_list[current_item['end']]) or \
                    (current_item['direction'] == TrendHelper.DIRECTION_DOWN and price_list[next_item['end']] <= price_list[current_item['end']])):
                    current_item = TrendHelper.extend_trend(price_list, current_item, next_item, trend_config, "end")
                    i += 1
                else:
                    current_item['vary_portion'] = (price_list[current_item['end']] - price_list[current_item['start']]) / price_list[current_item['start']] * 100
                    result_list.append(current_item)
                    current_item = item
            else:
                current_item['vary_portion'] = (price_list[current_item['end']] - price_list[current_item['start']]) / price_list[current_item['start']] * 100
                result_list.append(current_item)
                current_item = item

            i += 1

        result_list.append(current_item)
        return result_list
        '''

    @staticmethod
    def merge_same_trend(price_list, range_list, trend_config, options):
        loop_count = 0
        item_list = []
        verify_length = 'length' in options and options['length']
        verify_direction = 'direction' in options and options['direction']

        #print range_list
        # 合并连续相同的几段趋势节点, 最后1段震荡趋势也参与合并, 主要是用于合并震荡趋势节点
        # 对于连续length < 3的震荡趋势, 直接合并到一起
        # 对于length > 3 的震荡趋势, 必须要求是同方向, 才合并到一起, 合并多段震荡后会变为上涨/下跌
        while True:
            item_list = []
            index = 0
            need_loop = False

            while index < len(range_list):
                item = range_list[index]
                if 'trend' not in item:
                    item['trend'] = TrendHelper.get_trend_by_price(price_list[item['start']], price_list[item['end']], trend_config['trend_vary_portion'], trend_config['trend_vary_type'])
                if 'vary_portion' not in item:
                    item['vary_portion'] = (price_list[item['end']] - price_list[item['start']]) / price_list[item['start']] * 100

                # 连续2段长度为2的上涨/下跌趋势需要合并
                next_item = range_list[index + 1] if index < len(range_list) - 1 else None
                if next_item and not verify_direction and item['trend'] != TrendHelper.TREND_WAVE and next_item['trend'] != TrendHelper.TREND_WAVE and \
                    item['trend'] != next_item['trend'] and item['length'] < trend_config['min_trend_length'] and next_item['length'] < trend_config['min_trend_length']:
                    item = TrendHelper.extend_trend(price_list, item, next_item, trend_config, "end")
                    item_list.append(item)
                    need_loop = True
                    index += 2
                else:
                    offset = index

                    # 相邻同趋势的节点进行合并
                    while offset < len(range_list) - 2:
                        offset_item = range_list[offset + 1]
                        offset_item_trend = TrendHelper.get_trend_by_price(price_list[offset_item['start']], price_list[offset_item['end']], trend_config['trend_vary_portion'], trend_config['trend_vary_type'])

                        if offset_item_trend == item['trend']:
                            need_merge = False
                            if item['trend'] == TrendHelper.TREND_WAVE and ((loop_count == 0 and ((verify_length and item['length'] < trend_config['min_trend_length'] and offset_item['length'] < trend_config['min_trend_length']) or not verify_length)) or \
                                ((loop_count >= 1 and verify_direction and item['direction'] == offset_item['direction']) or not verify_direction)):
                                need_merge = True
                                need_loop = True
                            elif item['trend'] != TrendHelper.TREND_WAVE:
                                need_merge = True
                            else:
                                break

                            if need_merge:
                                offset += 1
                                # 合并时会重新计算trend, 多个震荡可能会合并为上涨/下跌, 所以每两个相邻节点合并, 一直循环只要trend不是震荡, 会终止合并
                                item = TrendHelper.extend_trend(price_list, item, range_list[offset], trend_config, "end")
                                index += 1
                        else:
                            break

                    item_list.append(item)
                    index += 1

            if need_loop:
                range_list = list(item_list)
                loop_count += 1
            else:
                break

        return item_list

    '''
        desc 解析价格列表为趋势阶段列表
        @param price_list list
        @param trend_config dict('trend_vary_portion', 'min_trend_length')
        @param merged boolean 标识是否需要归并小段趋势为大段趋势, 缺省为False
        @return list[{'start', 'end', 'direction', 'high_price', 'low_price', 'vary_portion', 'trend'}]
    '''
    @staticmethod
    def parse(price_list, trend_config, merged = False):
        range_list = TrendHelper.partition(price_list)

        # 把长度过小的趋势合并到相邻节点上, 确保每段趋势长度>=3
        item_list = TrendHelper.combine_list(price_list, range_list, trend_config)
        #print combined_list
        stage_list = TrendHelper.merge(price_list, item_list, trend_config) if merged else None
        return (item_list, stage_list)

    '''
        @desc 扩展延长趋势节点, 仅针对相邻或隔一个的节点调用。对于连续多个的震荡趋势节点，直接扩展尾部节点会导致中间出现上涨/下跌趋势被忽略
        @param price_list list
        @param trend_stage dict
        @param trend_item dict
        @param trend_config dict
        @param index string 缺省为end, 取值为high/low/end
        @return dict
    '''
    @staticmethod
    def extend_trend(price_list, trend_stage, trend_item, trend_config, index = "end"):
        extend_stage = trend_stage
        trend_item = trend_item
        extend_stage['end'] = trend_item[index]

        if "end" == index:
            extend_stage['high_price'] = max(trend_stage['high_price'], trend_item['high_price'])
            extend_stage['low_price'] = min(trend_stage['low_price'], trend_item['low_price'])
            extend_stage['length'] = extend_stage['end'] - extend_stage['start'] + 1
            #extend_stage['length'] += trend_item['length']

        extend_stage['vary_portion'] = (price_list[extend_stage['end']] - price_list[extend_stage['start']]) / price_list[extend_stage['start']] * 100
        extend_stage['direction'] = TrendHelper.DIRECTION_UP if price_list[extend_stage['end']] >= price_list[extend_stage['start']] else TrendHelper.DIRECTION_DOWN
        extend_stage['trend'] = TrendHelper.get_trend_by_price(price_list[extend_stage['start']], price_list[extend_stage['end']], trend_config['trend_vary_portion'], trend_config['trend_vary_type'])

        return extend_stage

    '''
        desc 对趋势节点列表进行归并, 重点是震荡趋势归并
        @param price_list list
        @param trend_list list
        @param trend_config dict('trend_vary_portion', 'min_trend_length')
        @return list[{'start', 'end', 'direction', 'high_price', 'low_price', 'vary_portion', 'trend'}]
    '''
    @staticmethod
    def merge(price_list, trend_list, trend_config):
        stage_list = []
        current_stage = None

        for item in trend_list:
            # 拷贝一份趋势节点, 避免归并过程中对已有节点修改
            trend_item = dict(item)

            # 标识是一段新的趋势
            if current_stage is None:
                current_stage = trend_item
            # 同方向趋势进行合并
            elif trend_item['trend'] == current_stage['trend']:
                current_stage = TrendHelper.extend_trend(price_list, current_stage, trend_item, trend_config, "end")
            # 当前趋势阶段为震荡, 趋势节点为上涨/下跌
            elif current_stage['trend'] == TrendHelper.TREND_WAVE:
                last_stage = stage_list[-1] if len(stage_list) > 0 else None
                # 中间为震荡节点, 前后2段趋势同方向 且 突破新高或新低时, 合并震荡前后的趋势
                if last_stage and last_stage['trend'] == trend_item['trend'] and ((trend_item['direction'] == TrendHelper.DIRECTION_UP and price_list[trend_item['end']] >= price_list[current_stage['end']]) or \
                    (trend_item['direction'] == TrendHelper.DIRECTION_DOWN and price_list[trend_item['end']] <= price_list[current_stage['end']])):
                    last_stage = TrendHelper.extend_trend(price_list, last_stage, trend_item, trend_config, "end")
                    current_stage = last_stage
                    stage_list.pop(-1)
                else:
                    stage_list.append(current_stage)
                    current_stage = trend_item
            else:
                stage_list.append(current_stage)
                current_stage = trend_item

        stage_list.append(current_stage)
        return stage_list

    '''
        @desc 根据阶段趋势分析当前趋势的阻力位/支撑位, 返回阻力位为0表示当前处于最高的上涨/下跌段, 支撑位为0表示处于最低的上涨/下跌段
        @param price_list list
        @param stage_list list
        @param trend_config dict
        @return dict(trend, resist_price, support_price, resist_vary_portion, support_vary_portion)
    '''
    @staticmethod
    def get_pivot(price_list, stage_list, trend_config):
        current_stage = stage_list[-1]
        pivot_info = dict()
        resist_price = 0.0
        support_price = 0.0

        # 当前趋势节点为上涨/下跌时, 寻找过去的同方向趋势节点, 获取阻力位/支撑位信息
        if current_stage['trend'] == TrendHelper.TREND_WAVE:
            last_stage = stage_list[-2] if len(stage_list) >= 2 else None
            if last_stage:
                trend = last_stage['trend']
                current_stage = last_stage
            else:
                return pivot_info
        else:
            trend = current_stage['trend']

        item_resist_price = price_list[current_stage["end"]] if current_stage['direction'] == TrendHelper.DIRECTION_UP else price_list[current_stage["start"]]
        item_support_price = price_list[current_stage["start"]] if current_stage['direction'] == TrendHelper.DIRECTION_UP else price_list[current_stage["end"]]

        # 上涨趋势时: 阻力/支撑位都是end, 下跌趋势时阻力是start, 支撑位是end
        # TODO: 阻力/支撑位是不是用high_price/low_price更合理, 这段逻辑待重构优化, 理论上阻力位/支撑位与上涨/下跌趋势无关
        same_trend_list = TrendHelper.rfind_same_trend(stage_list, len(stage_list) - 1, trend)
        if same_trend_list:
            for trend_item in same_trend_list:
                if trend_item['end'] == current_stage['end']:
                    continue

                if trend == TrendHelper.TREND_RISE:
                    if price_list[trend_item["end"]]  >= item_resist_price:
                        resist_price = price_list[trend_item["end"]] if resist_price == 0 else min(resist_price, price_list[trend_item["end"]])
                    elif price_list[trend_item["end"]] >= item_support_price:
                        support_price = price_list[trend_item["end"]] if support_price == 0 else max(support_price, price_list[trend_item["end"]])
                else:
                    if price_list[trend_item["start"]]  >= item_resist_price:
                        resist_price = price_list[trend_item["start"]] if resist_price == 0 else min(resist_price, price_list[trend_item["start"]])
                    elif price_list[trend_item["start"]] <= item_support_price:
                        support_price = price_list[trend_item["start"]] if support_price == 0 else max(support_price, price_list[trend_item["start"]])
                    elif price_list[trend_item["end"]] <= item_support_price:
                        support_price = price_list[trend_item["end"]] if support_price == 0 else max(support_price, price_list[trend_item["end"]])
        else:
            support_price = item_support_price #if current_stage['direction'] == TrendHelper.DIRECTION_UP else 0
            resist_price = item_resist_price #if current_stage['direction'] == TrendHelper.DIRECTION_DOWN else 0

        pivot_info['resist_price'] = resist_price
        pivot_info['support_price'] = support_price

        high_price = max(price_list[current_stage["end"]], price_list[current_stage["start"]])
        low_price = min(price_list[current_stage["end"]], price_list[current_stage["start"]])
        pivot_info['resist_vary_portion'] = (resist_price - high_price) / high_price * 100 if resist_price > 0 else 0
        pivot_info['support_vary_portion'] = abs((support_price - low_price) / low_price * 100) if support_price > 0 else 0

        return pivot_info

    '''
        @desc 解读最近一段时间区间的趋势
        @param price_list list 价格列表
        @param trend_config dict
        @param trend_info tuple (trend_list, stage_list)
        @param latest_count int 缺省为30
        @param dict('core_trend', 'active_trend', 'latest_stage_list': [])
    '''
    @staticmethod
    def explain_latest_trend(price_list, trend_config, trend_info, latest_count = 30):
        explain_info = dict()
        stage_list = trend_info[1]
        latest_stage_list = []

        if len(stage_list) == 1:
            stage_item = stage_list[-1]
            latest_stage_list.append(stage_item)
        elif len(price_list) < latest_count:
            latest_stage_list = stage_list
        else:
            # 最近区间的起始偏移, 从后往前找到其所在的stage
            latest_index = len(price_list) - latest_count

            index = len(stage_list) - 1
            while index >= 0:
                stage_item = stage_list[index]
                if stage_item['start'] <= latest_index and latest_index <= stage_item['end']:
                    real_length = stage_item['end'] - latest_index + 1
                    if real_length > trend_config['min_trend_length']:
                        # TODO: vary_portion/trend也需要重新计算
                        item = dict(stage_item)
                        item['start'] = latest_index
                        item['length'] = real_length
                        item['vary_portion'] = (price_list[item['end']] - price_list[latest_index]) / price_list[latest_index] * 100
                        latest_stage_list.append(item)
                    break
                else:
                    latest_stage_list.append(stage_item)
                index -= 1

            # 没找到肯定是出错了, 暂时容错
            if index < 0:
                print "err=no_latest_item"
                return None
            latest_stage_list.reverse()

        key_item = None
        if len(latest_stage_list) == 1:
            key_item = latest_stage_list[0]
        else:
            # 试图找到核心趋势段, 该趋势长度 >= 2/3 * latest_count, 若找到以该段趋势为准
            node_count = min(len(price_list), latest_count)
            key_list = filter(lambda item: item['length'] >= int(node_count * 2/3), latest_stage_list)
            if len(key_list) > 0:
                key_item = key_list[0]
            else:

                # 找出趋势长度最大的两段趋势 和 最近的一段趋势节点, 比较其trend和顺序
                length_sorted_list = sorted(latest_stage_list, key=lambda x: x['length'], reverse = True)
                max_item = length_sorted_list[0]
                second_item = length_sorted_list[1]

                # 存在2段长度相同的趋势节点, 忽略震荡趋势的节点
                if max_item['length'] == second_item['length']:
                    if max_item['trend'] == TrendHelper.TREND_WAVE:
                        max_item = second_item
                        second_item = length_sorted_list[2] if len(length_sorted_list) >= 3 else length_sorted_list[0]
                    elif second_item['trend'] == TrendHelper.TREND_WAVE:
                        second_item = length_sorted_list[2] if len(length_sorted_list) >= 3 else length_sorted_list[1]

                is_max_latest = (max_item['start'] > second_item['start'])
                # core_item 代表核心趋势的趋势段, active_item 代表当前趋势的趋势段
                core_item = None
                active_item = None

                # 最大长度 > 2倍第二大长度 且 最大长度趋势距离当前时间更近
                if float(max_item['length'] / second_item['length']) >= 2.0 and is_max_latest:
                    core_item = max_item
                    active_item = max_item
                else:
                    past_item = latest_stage_list[-1]
                    core_item = max_item if abs(max_item['vary_portion']) >= abs(second_item['vary_portion']) else second_item
                    if abs(core_item['vary_portion']) < abs(past_item['vary_portion']):
                        core_item = past_item

                    # active_item非最后一段趋势且最后一段趋势为上涨/下跌时, 认为它代表当前趋势
                    non_wave_list = filter(lambda item: item['trend'] != TrendHelper.TREND_WAVE, latest_stage_list)
                    active_item = non_wave_list[-1]

                explain_info['core_item'] = core_item
                explain_info['active_item'] = active_item

        if key_item:
            explain_info['core_item'] = explain_info['active_item'] = key_item

        explain_info['latest_stage_list'] = latest_stage_list
        return explain_info

    '''
        @desc 对趋势节点列表进行归并, 重点是震荡趋势归并
        @param price_list list
        @param trend_config dict('trend_vary_type', 'trend_vary_portion', 'min_trend_length', 'stage_vary_portion', 'daily_vary_portion', 'latest_count')
        @return dict(trend_list, daily_trend, latest_trend, pivot)
    '''
    @staticmethod
    def core(price_list, trend_config):
        if 'trend_vary_type' not in trend_config:
            trend_config['trend_vary_type'] = "portion"
        (trend_list, stage_list) = TrendHelper.parse(price_list, trend_config, True)

        latest_trend = dict()
        daily_trend = TrendHelper.get_trend_by_price(price_list[0], price_list[-1], trend_config['daily_vary_portion'], trend_config['trend_vary_type'])
        if stage_list:
            pivot_info = TrendHelper.get_pivot(price_list, stage_list, trend_config)
            if len(price_list) >= int(trend_config['latest_count'] / 2):
                latest_trend = TrendHelper.explain_latest_trend(price_list, trend_config, (trend_list, stage_list), trend_config['latest_count'])
        else:
            pivot_info = None
            latest_trend = None

        return {'trend_list': trend_list, 'stage_list': stage_list, 'daily_trend': daily_trend, 'pivot': pivot_info, 'latest_trend':latest_trend}

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print "Usage:" + sys.argv[0] + " <filename> <sid>"
        sys.exit(1)

    sid = int(sys.argv[2])
    import_datamap = loaddata(sys.argv[1])
    print import_datamap
    daily_list = import_datamap['daily'][sid]
    items = import_datamap['realtime'][sid]

    step = 5
    index = 0
    min_count = len(items)
    trend_config = {'trend_vary_portion': 1.0, 'min_trend_length': 3, 'stage_vary_portion': 1.0, 'daily_vary_portion': 2.0, 'latest_count': 30}
    #print min_count

    trend_snapshot_list = []
    while index <= min_count:
        index += 5
        offset = min(index, min_count)
        price_list = [ minute_item['price'] for minute_item in items[0 : offset] ]
        close_price = items[offset]['price'] if offset < min_count else items[-1]['price']

        daily_item = daily_list[index] if index < len(daily_list) else daily_list[-1]
        daily_item["high_price"] = max(price_list)
        daily_item['low_price'] = min(price_list)
        daily_item['close_price'] = close_price
        daily_item['vary_portion'] = (close_price - daily_item['last_close_price']) / daily_item['last_close_price'] * 100

        trend_info = TrendHelper.core(price_list, trend_config)
        print index, "-------------------------"
        print trend_info['pivot']
        if trend_info['latest_trend']:
            trend_snapshot_list.append((index, trend_info['latest_trend']['core_item']['trend'], trend_info['latest_trend']['active_item']['trend'], close_price, trend_info['pivot']))
            print trend_info['latest_trend']['core_item']['trend'], trend_info['latest_trend']['active_item']['trend']
            print trend_info['latest_trend']['core_item'], trend_info['latest_trend']['active_item']

        #print trend_info['latest_trend']['latest_stage_list']
        print "-------"
        for trend_item in trend_info['trend_list']:
            print trend_item, trend_item['low_price'], trend_item['high_price']
        print "-------"
        for stage_info in trend_info['stage_list']:
            print stage_info

    for item in trend_snapshot_list:
        print item

    print "finish"
