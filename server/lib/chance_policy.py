#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 操作机会决策分析
#date: 2016/07/02

import sys, re, json, os
import datetime, time
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
import redis, pymysql, numpy
import pandas as pd
from base_policy import BasePolicy
from minute_trend import MinuteTrend
from stock_util import get_stock_info


'''
    @desc: 对所有操作机会进行筛选过滤排序, 决策最终执行的操作
        1) 当日涨/跌幅 >= 6%, 不宜追入
        2) 价格高于昨日收盘, 不建议做空
        3) 大盘走势整体为下跌, 且当前价格离昨日收盘超过2%, 不建议做多
        3) 结合大盘走势、股票近期日K走势、开盘价格和日内趋势指定股票日内走势方向, 尽量同方向操作
        3) 已买入的股票组合由PortofioManager进行管理
        4) 对于已买入的股票, 提示卖出点时, 检查是否触及止损位, 触及直接卖出; 否则看是否达到3%-5%的界限, 达到也卖出; 没有提示则收盘卖出

'''
class ChancePolicy(BasePolicy):
    # 保存操作过的item, 可用sid -> time来唯一标识每个item
    item_map = dict()
    # 持仓操作的股票 sid -> {order_event, closed}
    stock_map = dict()
    # 大盘sid: location -> sid, A股选择上证指数, 美股为道琼斯指数
    dapan_map = {1: 2469, 3: 9609}
    # 建仓时间段
    chance_config = {3: {'open_time': 2130, 'deadline_time': 1200, 'stop_portion': 2.00, 'profit_portion': (3.00, 6.00)}}
    traded_count = 0

    '''
    @desc 过滤不合适的操作机会
    @param item dict
    @return None
    '''
    def filter(self, item):
        sid = item['sid']
        day = item['daily_item']['day']

        chance_info = item['chance']
        daily_item = item['daily_item']
        day_vary_portion = (daily_item['close_price'] - daily_item['open_price']) / daily_item['open_price'] * 100

        # 日内涨幅 >= 6%不建议追高
        if chance_info['op'] == MinuteTrend.OP_LONG and abs(day_vary_portion) >= 6.00:
            return

        # TODO: 从redis中获取大盘趋势
        key = "chance-" + str(sid) + "-" + str(day)
        self.redis_conn.rpush(key, json.dumps(item))

        # 全局list, 倒序排列
        self.redis_conn.lpush("chance-" + str(day), json.dumps(item))
        #self.logger.debug("%s", format_log("chance_item", item))

        chance_count = self.redis_conn.llen("chance-" + str(day))
        if chance_count % 5 == 0:
            self.rank(3, daily_item['day'], item['time'])

    '''
    @desc 定时运行对目前的操作机会进行综合排序, 每次取出最近前20个, 得到top3
    @param location int
    @param day int
    @param cur_timenumber int 格式为HHMM
    @return
    '''
    def rank(self, location, day, cur_timenumber):
        # 这里按时间倒序拉取最近20个操作, 会出现在前面排序靠后的操作, 在后面的时间被执行了, 需要加上时间范围限制
        data_list = self.redis_conn.lrange("chance-" + str(day), 0, 20)
        if data_list is None or len(data_list) == 0:
            pass

        dapan_trend = MinuteTrend.TREND_WAVE
        dapan_sid = self.dapan_map[location]
        daily_cache_value = self.redis_conn.get("daily-"+ str(dapan_sid) + "-" + str(day))
        dapan_data = json.loads(daily_cache_value) if daily_cache_value is not None else dict()
        if dapan_data:
            dapan_trend = MinuteTrend.TREND_RISE if (dapan_data['close_price'] - dapan_data['last_close_price']) >= 50 else MinuteTrend.TREND_FALL

        item_list = []
        cur_timenumber = 0
        for data in data_list:
            item = json.loads(data)
            sid = item['sid']
            item_key = (item['sid'], item['time'])

            if cur_timenumber == 0:
                cur_timenumber = item['time']
            # 超过20min则不执行
            elif cur_timenumber - item['time'] >= 20:
                self.logger.debug("desc=ignore_expired_chance location=%d sid=%d code=%s day=%d time=%d cur_time=%d",
                                  location, sid, item['code'], day, item['time'], cur_timenumber)
                continue

            # 获取最新的价格信息
            daily_cache_value = self.redis_conn.get("daily-"+ str(sid) + "-" + str(day))
            daily_item = json.loads(daily_cache_value) if daily_cache_value else item['daily_item']

            # 操作机会已经交易过直接忽略
            if sid in self.item_map and self.item_map[sid] == item['time']:
                self.logger.info("desc=chance_traded location=%d sid=%d code=%s day=%d time=%d", location, sid, item['code'], day, item['time'])
                continue
            # 该股票的同方向操作已经交易, 直接忽略
            elif sid in self.stock_map:
                # 同方向超过12点的、相反方向的机会可用于尝试平仓
                order_event = self.stock_map[sid]['order']
                if item['chance']['op'] != order_event['chance']['op'] or item['time'] > self.chance_config[location]['deadline_time']:
                    self.close_position(location, day, sid, item)
                    continue
                # 相反的方向提示平仓
                else:
                    self.logger.info("desc=chance_exist location=%d sid=%d code=%s day=%d time=%d", location, sid, item['code'], day, item['time'])
                    continue
            # 价格高于max(昨日收盘价, 当日开盘价), 不建议做空, 考虑到当日高开后低走下跌, 这种情况下低于开盘价也OK
            elif item['chance']['op'] == MinuteTrend.OP_SHORT and daily_item['close_price'] >= max(daily_item['last_close_price'], daily_item['open_price']):
                self.logger.info("desc=short_not_match location=%d sid=%d code=%s day=%d time=%d op=%d close_price=%.2f",
                                 location, sid, item['code'], day, item['time'], item['chance']['op'], daily_item['close_price'])
                continue
            # 操作机会随着大盘趋势演变, 还有可能进入视野
            elif (dapan_trend == MinuteTrend.TREND_RISE and item['chance']['op'] == MinuteTrend.OP_SHORT) or (dapan_trend == MinuteTrend.TREND_FALL and item['chance']['op'] == MinuteTrend.OP_LONG):
                self.logger.info("desc=ignore_contray_stock location=%d sid=%d code=%s day=%d dapan=%d time=%d op=%d",
                            location, sid, item['code'], day, dapan_trend, item['time'], item['chance']['op'])
                continue
            # 判断股票市值, 必须>=5亿刀 <= 300亿刀
            else:
                cache_value = self.redis_conn.get("stock:info-" + str(sid))
                if cache_value:
                    stock_info = json.loads(cache_value)
                else:
                    stock_info = get_stock_info(self.config_info["DB"], sid)

                market_cap = float(stock_info['capital']) * daily_item['close_price'] / 10000
                if market_cap <= 5 or market_cap > 300:
                    self.logger.info("desc=ignore_small_cap location=%d sid=%d code=%s day=%d time=%d op=%d capital=%s close_price=%.2f market_cap=%.2f",
                        location, sid, item['code'], day, item['time'], item['chance']['op'], stock_info['capital'], daily_item['close_price'], market_cap)
                    continue

            item_list.append(item)

        # 尝试对已建仓的股票进行平仓
        if cur_timenumber > self.chance_config[location]['deadline_time']:
            for sid, stock_open_info in self.stock_map.items():
                if stock_open_info['closed']:
                    continue
                self.close_position(location, day, sid, None)

        # 按照多个维度进行倒序排列
        if len(item_list) > 0:
            #item_list.sort(key=lambda item: (abs(item['trend_item']['vary_portion']/item['trend_item']['length']), item['daily_trend'], abs(item['daily_item']['vary_portion']) / (item['time'] - 2130)), reverse=True)
            #print item_list
            chance_df = self.sort_chance(location, day, item_list)
            print chance_df

            limit_count = 2
            for sid, row in chance_df.iterrows():
                # 超过建仓时间 或者 趋势强度 < 0.10, 不考虑建仓
                if row['time'] > self.chance_config[location]['deadline_time'] or row['trend_strength'] < 0.10:
                    continue
                elif sid in self.item_map:
                    continue

                if limit_count > 0:
                    search_item_list = [item for item in item_list if item['sid'] == sid and item['time'] == row['time']]
                    if len(search_item_list) != 1:
                        continue

                    item = search_item_list[0]
                    open_result = self.open_position(location, day, item['sid'], item)
                    if open_result:
                        self.item_map[item['sid']] = item['time']
                        limit_count -= 1
                else:
                    break

    '''
    @desc 根据操作机会进行交易建仓
    @param location int
    @param day int
    @param sid int
    @param item chance_item
    @return boolean True 表示建仓成功 False 建仓失败
    '''
    def open_position(self, location, day, sid, item):
        # 获取该股票所有的操作机会
        same_count = 1
        contray_count = 0

        key = "chance-" + str(sid) + "-" + str(day)
        # 该机会不一定是最新的1个
        chance_item_list = self.redis_conn.lrange(key, 0, -1)
        for chance_item_data in chance_item_list:
            chance_item = json.loads(chance_item_data)
            # 忽略自己
            if chance_item['time'] == item['time']:
                continue

            if chance_item['chance']['op'] == item['chance']['op']:
                same_count += 1
            else:
                contray_count += 1

        # 直接买入
        print same_count, contray_count
        if contray_count == 0 or same_count > contray_count:
            # TODO: 调用PortfioManager进行持仓管理, 推送交易事件(order_event)
            order_event = {'sid': sid, 'day': day, 'code': item['code'], 'time': item['time']}
            #open_price = (item['chance']['price_range'][0] + item['chance']['price_range'][1]) / 2
            stop_price = item['chance']['stop_price']
            #if item['daily_trend'] != MinuteTrend.TREND_WAVE :

            # 开盘价目前暂时直接取最大的, 便于立即建仓
            if item['chance']['op'] == MinuteTrend.OP_LONG:
                open_price = item['chance']['price_range'][1]
                stop_price = min(stop_price, open_price * (1 - self.chance_config[location]['stop_portion'] / 100))
            else:
                open_price = item['chance']['price_range'][0]
                stop_price = max(stop_price, open_price * (1 + self.chance_config[location]['stop_portion'] / 100))

            order_event['chance'] = item['chance']
            order_event['open_price'] = open_price
            order_event['stop_price'] = stop_price
            self.stock_map[sid] = {'order': order_event, 'closed': False}
            self.logger.info("%s", format_log("open_position", order_event))
            return True

        return False

    '''
    @desc 根据操作机会提示进行平仓
    @param location int
    @param day int
    @param sid int
    @param item chance_item 可选
    @return
    '''
    def close_position(self, location, day, sid, item):
        print "enter_close location=" + str(location) + " day=" + str(day) + " sid=" + str(sid)
        if sid not in self.stock_map or self.stock_map[sid]['closed']:
            self.logger.debug("desc=check_close location=%d sid=%d day=%d", location, sid, day);
            return

        stock_open_info = self.stock_map[sid]
        order_event = stock_open_info['order']
        # TODO: 回测时daily_item中的为收盘价, 且item['daily_item']的价格并不是实时更新的
        daily_cache_value = self.redis_conn.get("daily-"+ str(sid) + "-" + str(day))
        daily_item = json.loads(daily_cache_value) if daily_cache_value else None

        # 反方向的操作机会时, 立即卖出
        if item is not None and item['chance']['op'] != order_event['chance']['op']:
            if daily_item is None:
                daily_item = item['daily_item']
        if daily_item is None:
            self.logger.info("err=close_get_daily location=%d sid=%d code=%s day=%d", location, sid, order_event['code'], day);
            return

        #TODO: 需要利用订单实际成交的价格来计算目前获利和止损
        current_timenumber = item['time'] if item is not None else int(daily_item['time']/100)
        # TODO: 临时加的, 修正构造数据中time不对的问题
        if current_timenumber < 930:
            current_timenumber += 1200

        current_price = daily_item['close_price']
        vary_portion = (current_price - order_event['open_price']) / order_event['open_price'] * 100
        if order_event['chance']['op'] == MinuteTrend.OP_SHORT:
            vary_portion = -1 * vary_portion
        need_close = False

        self.logger.debug("%s location=%d day=%d current_time=%d current_price=%.2f vary_portion=%.2f",
                format_log("close_detail", order_event), location, day, current_timenumber, current_price, vary_portion);

        # 需要平仓: 越过止损位/出现反方向趋势且获利达到最小要求/临近收盘且时间>=1530
        if (order_event['chance']['op'] == MinuteTrend.OP_LONG and current_price <= order_event['stop_price']) or (order_event['chance']['op'] == MinuteTrend.OP_SHORT and current_price >= order_event['stop_price']):
            self.logger.info("desc=stop_close location=%d sid=%d code=%s day=%d op=%d time=%d open_price=%.2f current_price=%.2f stop_price=%.2f vary_portion=%.2f",
                             location, sid, order_event['code'], day, order_event['chance']['op'], current_timenumber, order_event['open_price'], current_price, order_event['stop_price'], abs(vary_portion))
            need_close = True
        elif item is not None and abs(vary_portion) >= self.chance_config[location]['profit_portion'][0]:
            self.logger.info("desc=profit_close location=%d sid=%d code=%s day=%d op=%d time=%d open_price=%.2f current_price=%.2f stop_price=%.2f vary_portion=%.2f",
                             location, sid, order_event['code'], day, order_event['chance']['op'], item['time'], order_event['open_price'], current_price, order_event['stop_price'], abs(vary_portion))
            need_close = True
        elif current_timenumber >= 1500:
            self.logger.info("desc=time_close location=%d sid=%d code=%s day=%d op=%d time=%d open_price=%.2f current_price=%.2f stop_price=%.2f vary_portion=%.2f",
                             location, sid, order_event['code'], day, order_event['chance']['op'], current_timenumber, order_event['open_price'], current_price, order_event['stop_price'], abs(vary_portion))
            need_close = True

        #TODO: 调用订单平仓
        if need_close:
            self.stock_map[sid]['closed'] = True
            self.logger.info("desc=close_position location=%d sid=%d code=%s day=%d time=%d op=%d open_price=%.2f close_price=%.2f stop_price=%.2f vary_portion=%.2f",
                location, sid, order_event['code'], day, current_timenumber, order_event['chance']['op'], order_event['open_price'], current_price, order_event['stop_price'], vary_portion)
        print "exit_close sid=" + str(sid) + " current_time=" + str(current_timenumber) + " need_close=" + str(need_close)

    '''
    @desc 对操作机会的股票进行综合排序, 基于股票多个维度的特征,类似于搜索的排序
          目前结合股票市值、最近5日/30日换手率、最近5日/30日振幅、股票日趋势
        TODO: 后续抽取成通用的排序引擎, 短线/中线/长线的对应特征不一样
    @param location int
    @param day int
    @param item_list list
    @return DataFrame(sid -> dict)
    '''
    def sort_chance(self, location, day, item_list):
        stock_chance_map = dict()
        for item in item_list:
            sid = item['sid']
            trend_strength = abs(item['trend_item']['vary_portion']/item['trend_item']['length'])
            vary_portion_strength = abs(item['daily_item']['vary_portion']) / (item['time'] - 2130)
            if sid not in stock_chance_map:
                elem = dict()
                elem['count'] = 1
                elem['time'] = item['time']
                elem['trend_strength'] = trend_strength
                elem['vary_portion_strength'] = trend_strength
                elem['daily_trend'] = item['daily_trend']
                stock_chance_map[sid] = elem
            else:
                stock_chance_map[sid]['count'] += 1
                stock_chance_map[sid]['trend_strength'] = max(trend_strength, stock_chance_map[sid]['trend_strength'])
                stock_chance_map[sid]['vary_portion_strength'] = max(vary_portion_strength, stock_chance_map[sid]['vary_portion_strength'])

        # 根据股票列表读取最近5天的股票动态信息
        sid_list = stock_chance_map.keys()
        charset = self.db_config['charset'] if 'charset' in self.db_config else 'utf8'
        conn = pymysql.connect(self.db_config['host'], self.db_config['username'], self.db_config['password'], self.db_config.get('database'),int(self.db_config['port']), charset=charset)

        sql = "select * from t_stock_dyn where sid in ({sid_list}) and day <= {day} order by day desc limit 5".format(sid_list=",".join(sid_list), day=day)
        print sql
        dyn_df = pd.read_sql_query(sql, conn, index_col="id")
        for sid in sid_list:
            stock_df = pd.query('sid=' + str(sid), index_col='day')
            print stock_df
            current_row = stock_df.loc[day]
            vary_portion_series = stock_df['ma5_vary_portion']
            swing_portion_series = stock_df['ma5_swing']
            exchange_portion_series = stock_df['ma5_exchange_portion']

            matched = True
            # 最近5日平均涨跌幅<=2% 且5日平均涨跌幅最大值<3%
            if abs(current_row['ma5_vary_portion']) <= 2 or vary_portion_series.max() < 3:
                matched = False
            # 最近5日平均振幅<=3% 且5日平均涨跌幅最大值<5%
            elif abs(swing_portion_series.mean()) <= 3 or swing_portion_series.max() < 5:
                matched = False
            elif current_row['ma5_exchange_portion'] < 0.75 or exchange_portion_series.mean() < 1:
                matched = False

            if not matched:
                self.logger.info("op=ignore_nonmatch_stock sid=%d location=%d day=%d %s", sid, location, day, format_log(current_row))
                del stock_chance_map[sid]
                continue

            stock_chance_map[sid]['ma5_vary_portion'] = abs(current_row['ma5_vary_portion'])
            stock_chance_map[sid]['ma5_swing'] = abs(current_row['ma5_swing'])
            stock_chance_map[sid]['ma5_exchange_portion'] = current_row['ma5_exchange_portion']

        chance_df = pd.DataFrame(stock_chance_map)
        result_df = chance_df.sort_values(by=['trend_strength', 'ma5_swing', 'count', 'vary_portion_strength', 'ma5_exchange_portion', 'ma5_vary_portion'], ascending=False)
        return result_df
