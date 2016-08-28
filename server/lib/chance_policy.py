#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 操作机会决策分析
#date: 2016/07/02

import sys, re, json, os
import datetime, time, traceback
sys.path.append('../../../../server')
from pyutil.util import safestr, format_log
import redis, pymysql, numpy
import pandas as pd
from stock_util import get_stock_info
from base_policy import BasePolicy
from trend_helper import TrendHelper
from minute_trend import MinuteTrend
from portfolio_manager import PortfolioManager


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
    # 持仓操作的股票 sid -> order_info
    stock_map = dict()
    last_chance_count = 0

    '''
        机会配置: location -> chance_config
            index_stock 大盘指数sid
            open_time 开盘时间
            open_deadline_time 最晚建仓时间
            close_deadline_time 最晚平仓时间
            stop_portion 止损幅度
            profit_portion 最小盈利幅度
            close_portion_cond 每个时间段强制平仓的盈利幅度配置
    '''
    chance_config = {3: {'index_stock': 9609, 'open_time': 2130, 'open_deadline_time': 1300, 'close_deadline_time': 1500, 'stop_portion': 2.00, 'profit_portion': 3.00, 'close_portion_cond': [(1400, 4.00), (1300, 6.00), (1200, 8.00), (1100, 10.00), (1000, 10.00)]}}
    # 忽略的股票: sid
    ignore_set = dict()
    traded_count = 0

    def initialize(self, location, day):
        portfolio_config = {"initial_money": 4000, "max_short_stock": 2, "max_stock_count": 4, "max_stock_portion": 0.5, "max_trade_count": 8, "trade_period": {"interval": 30, "threshold": 2}}
        self.portfolio = PortfolioManager(location, day, self.config_info, portfolio_config)

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

        # 日内涨幅 >= 6%且操作时间在10:20之后, 不建议追高
        if chance_info['op'] == MinuteTrend.OP_LONG and item['time'] >= 1020 and abs(day_vary_portion) >= 6.00:
            return

        # TODO: 从redis中获取大盘趋势
        key = "chance-" + str(sid) + "-" + str(day)
        self.redis_conn.rpush(key, json.dumps(item))

        # 全局list, 倒序排列
        self.redis_conn.lpush("chance-" + str(day), json.dumps(item))
        self.logger.debug("%s", format_log("chance_item", item))

        '''
        # 通过time-queue的时间来调度, 不需要
        chance_count = self.redis_conn.llen("chance-" + str(day))
        if chance_count % 5 == 0:
            self.rank({'location':3, 'day': daily_item['day'], 'time': item['time'] * 100})
        '''
    '''
    @desc 定时运行对目前的操作机会进行综合排序, 每次取出最近前20个, 得到top3
    @param item {location, day, time}, time格式为HHMM
    @return
    '''
    def rank(self, item):
        location = item['location']
        day = item['day']
        cur_timenumber = int(item['time'] / 100)

        # 从持仓管理组合中获取快照信息
        self.stock_map = self.portfolio.get_portfolio(PortfolioManager.STATE_ALL)

        # 判断是否有新增操作机会, 没有新增则不需要拉取chance进行排序建仓
        data_list = []
        chance_count = self.redis_conn.llen("chance-" + str(day))
        if chance_count > self.last_chance_count:
            # 这里按时间倒序拉取最近20个操作, 会出现在前面排序靠后的操作, 在后面的时间被执行了, 需要加上时间范围限制
            data_list = self.redis_conn.lrange("chance-" + str(day), 0, 20)
            if data_list is None or len(data_list) == 0: 
                return

        self.last_chance_count = chance_count
        dapan_trend = TrendHelper.TREND_WAVE
        dapan_sid = self.chance_config[location]['index_stock']
        daily_cache_value = self.redis_conn.get("daily-"+ str(dapan_sid) + "-" + str(day))
        dapan_data = json.loads(daily_cache_value) if daily_cache_value is not None else dict()

        if dapan_data:
            dapan_trend = TrendHelper.TREND_RISE if (dapan_data['close_price'] - dapan_data['last_close_price']) >= 50 else TrendHelper.TREND_FALL

        item_list = []
        for data in data_list:
            item = json.loads(data)
            sid = item['sid']
            item_key = (item['sid'], item['time'])
            if sid in self.ignore_set:
                continue

            # 超过20min则不执行
            hour_diff = int(cur_timenumber / 100) - int(item['time'] / 100)
            min_diff = int(cur_timenumber % 100) - int(item['time'] % 100)
            if hour_diff * 60 + min_diff > 20:
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
                stock_order = self.stock_map[sid]
                if (stock_order['state'] == PortfolioManager.STATE_OPENED) and (item['chance']['op'] != stock_order['op']):
                    self.close_position(location, day, cur_timenumber, sid, item)
                    continue

            # 价格高于max(昨日收盘价, 当日开盘价)*(1-3%), 不建议做空, 考虑到当日高开后低走下跌, 这种情况下低于开盘价也OK
            elif item['chance']['op'] == MinuteTrend.OP_SHORT: 
                base_price = max(daily_item['last_close_price'], daily_item['open_price'])
                vary_portion = (daily_item['close_price'] - base_price) / base_price * 100
                if vary_portion >= -3.0:
                    self.logger.info("desc=short_not_match location=%d sid=%d code=%s day=%d time=%d op=%d close_price=%.2f base_price=%.2f vary_portion=%.2f",
                                 location, sid, item['code'], day, item['time'], item['chance']['op'], daily_item['close_price'], base_price, vary_portion)
                    continue

            # 操作机会随着大盘趋势演变, 还有可能进入视野
            elif (dapan_trend == TrendHelper.TREND_RISE and item['chance']['op'] == MinuteTrend.OP_SHORT) or (dapan_trend == TrendHelper.TREND_FALL and item['chance']['op'] == MinuteTrend.OP_LONG):
                self.logger.info("desc=ignore_contray_stock location=%d sid=%d code=%s day=%d dapan=%d time=%d op=%d",
                            location, sid, item['code'], day, dapan_trend, item['time'], item['chance']['op'])
                continue

            # 判断股票市值, 必须>=5亿刀 <= 300亿刀
            cache_value = self.redis_conn.get("stock:info-" + str(sid))
            if cache_value:
                stock_info = json.loads(cache_value)
            else:
                stock_info = get_stock_info(self.config_info["DB"], sid)

            market_cap = float(stock_info['capital']) * daily_item['close_price'] / 10000
            if market_cap <= 5 or market_cap > 300:
                self.ignore_set[sid] = True
                self.logger.info("desc=ignore_small_cap location=%d sid=%d code=%s day=%d time=%d op=%d capital=%s close_price=%.2f market_cap=%.2f",
                    location, sid, item['code'], day, item['time'], item['chance']['op'], stock_info['capital'], daily_item['close_price'], market_cap)
                continue

            item_list.append(item)

        # 尝试对已建仓的股票进行平仓
        for sid, stock_order in self.stock_map.items():
            if stock_order['state'] == PortfolioManager.STATE_OPENED:
                self.close_position(location, day, cur_timenumber, sid, None)

        # 按照多个维度进行倒序排列
        if len(item_list) > 0:
            #item_list.sort(key=lambda item: (abs(item['trend_item']['vary_portion']/item['trend_item']['length']), item['daily_trend'], abs(item['daily_item']['vary_portion']) / (item['time'] - 2130)), reverse=True)
            #print item_list
            chance_df = self.sort_chance(location, day, item_list)
            if chance_df is None:
                self.logger.info("desc=no_match_chance location=%d day=%d time_number=%d", location, day, cur_timenumber)
                return

            self.logger.info("desc=sort_chance location=%d day=%d time_number=%d chance=%s", location, day, cur_timenumber, chance_df.to_json())
            limit_count = 2
            for sid, row in chance_df.iterrows():
                # 超过建仓时间 或者 趋势强度 < 0.10, 不考虑建仓
                if row['time'] > self.chance_config[location]['open_deadline_time'] or row['trend_strength'] < 0.10:
                    continue
                elif sid in self.item_map:
                    continue

                if limit_count > 0:
                    search_item_list = [item for item in item_list if item['sid'] == sid and item['time'] == row['time']]
                    if len(search_item_list) != 1:
                        continue

                    item = search_item_list[0]
                    open_result = self.open_position(location, day, cur_timenumber, item['sid'], item)
                    if open_result:
                        self.item_map[item['sid']] = item['time']
                        limit_count -= 1
                else:
                    break

    '''
    @desc 根据操作机会进行交易建仓
    @param location int
    @param day int
    @param cur_timenumber int
    @param sid int
    @param item chance_item
    @return boolean True 表示建仓成功 False 建仓失败
    '''
    def open_position(self, location, day, cur_timenumber, sid, item):
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
        #print same_count, contray_count
        if contray_count == 0 or same_count > contray_count:
            order_event = {'sid': sid, 'day': day, 'code': item['code'], 'time': item['time']}
            stop_price = item['chance']['stop_price']

            if item['chance']['op'] == MinuteTrend.OP_LONG:
                open_price = item['chance']['price_range'][1]
                stop_price = min(stop_price, open_price * (1 - self.chance_config[location]['stop_portion'] / 100))
            else:
                open_price = item['chance']['price_range'][0]
                stop_price = max(stop_price, open_price * (1 + self.chance_config[location]['stop_portion'] / 100))

            # <=1000时建仓, 取区间的中间价格作为建仓价
            if item['time'] <= 1000:
                open_price = (item['chance']['price_range'][0] + item['chance']['price_range'][1]) / 2

            order_event['open_price'] = open_price
            order_event['stop_price'] = stop_price
            order_event['op'] = item['chance']['op']

            # 调用PortfioManager进行建仓
            open_result = self.portfolio.open(sid, order_event)
            self.logger.info("%s open_result=%s", format_log("open_position", order_event), str(open_result))
            return True

        return False

    '''
    @desc 根据操作机会提示进行平仓
    @param location int
    @param day int
    @param cur_timenumber int
    @param sid int
    @param item chance_item 可选
    @return
    '''
    def close_position(self, location, day, cur_timenumber, sid, item):
        if sid not in self.stock_map or self.stock_map[sid]['state'] >= PortfolioManager.STATE_WAIT_CLOSE:
            self.logger.debug("desc=stock_closed_already location=%d sid=%d day=%d", location, sid, day);
            return

        stock_order = self.stock_map[sid]
        daily_item = self.get_stock_currentinfo(sid, day)
        if daily_item is None:
            return

        current_price = daily_item['close_price']
        stock_time = daily_item['time']

        vary_portion = (current_price - stock_order['open_price']) / stock_order['open_price'] * 100
        if stock_order['op'] == MinuteTrend.OP_SHORT:
            vary_portion = -1 * vary_portion

        need_close = False
        reason = ""

        # 尝试获利平仓
        if vary_portion > 0:
            for close_portion_item in self.chance_config[location]['close_portion_cond']:
                (hour_time, hour_portion) = close_portion_item
                if stock_time >= hour_time and vary_portion >= hour_portion:
                    need_close = True
                    reason = "profit"
                    break
        # 止损平仓: 越过止损位
        if not need_close and (stock_order['op'] == MinuteTrend.OP_LONG and current_price <= stock_order['stop_price']) or (stock_order['op'] == MinuteTrend.OP_SHORT and current_price >= stock_order['stop_price']):
            reason = "stop"
            need_close = True
        # 超过指定时间平仓
        if not need_close and stock_time >= self.chance_config[location]['close_deadline_time']:
            reason = "time"
            need_close = True
        # TODO: 出现反方向机会时, 需要结合最近30min趋势来分析是否平仓, 暂时立即平仓
        if not need_close and item is not None and item['chance']['op'] != stock_order['op']:
            reason = "pivot"
            need_close = True

        item_json = "" if item is None else json.dumps(item)
        self.logger.info("%s need_close=%s reason=%s location=%d day=%d time=%d current_price=%.2f vary_portion=%.2f item=%s",
                    format_log("close_detail", stock_order), str(need_close), reason, location, day, stock_time, current_price, abs(vary_portion), item_json)

        #TODO: 调用订单平仓, 这里需要注意下单平仓后, 成交之前重复下单
        if need_close:
            close_item = {'sid': sid, 'day': day, 'code': daily_item['code'], 'time': cur_timenumber, 'price': current_price}
            close_item['op'] = MinuteTrend.OP_SHORT if stock_order['op'] == MinuteTrend.OP_LONG else MinuteTrend.OP_LONG
            self.portfolio.close(sid, close_item)

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
                elem['code'] = item['code']
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
        #print sid_list

        db_config = self.config_info['DB']
        charset = db_config['charset'] if 'charset' in db_config else 'utf8'
        conn = pymysql.connect(db_config['host'], db_config['username'], db_config['password'], db_config.get('database'), int(db_config['port']), charset=charset)

        columns = []
        for sid in sid_list:
            try:
                sql = "select * from t_stock_dyn where sid = {sid} and day < {day} order by day desc limit 5".format(sid=sid, day=day)
                #print sql

                stock_df = pd.read_sql_query(sql, conn, index_col="day")
                #print stock_df
                if stock_df.empty:
                    del stock_chance_map[sid]
                    continue

                current_row = stock_df.iloc[0]
                swing_portion_series = stock_df['ma5_swing']
                '''
                TODO: 考虑取最近5日的日数据, 用max来判断, ma5本身就是平均值
                vary_portion_series = stock_df['ma5_vary_portion']
                exchange_portion_series = stock_df['ma5_exchange_portion']
                '''

                # 前5日的5日平均振幅都>=3, 跳过检查
                skip_swing = False if swing_portion_series.mean() >= 3 or current_row['ma20_swing'] >= 3 else True

                # 日内交易强调波动性: 重点判断振幅和换手率, 涨跌幅弱化
                # 最近5日平均涨跌幅<=1% 或 最近5日平均振幅 <= 2 或  最近5日平均换手率 < 0.75
                if (skip_swing and current_row['ma5_swing'] <= 2) or abs(current_row['ma5_vary_portion']) <= 1 or current_row['ma5_exchange_portion'] < 0.75:
                    self.logger.info("op=ignore_nonmatch_stock sid=%d code=%s location=%d day=%d dyn=%s", sid, stock_chance_map[sid]['code'], location, day, current_row.to_json(orient="index"))
                    self.ignore_set[sid] = True
                    del stock_chance_map[sid]
                    continue

                stock_chance_map[sid]['ma5_vary_portion'] = abs(current_row['ma5_vary_portion'])
                stock_chance_map[sid]['ma5_swing'] = abs(current_row['ma5_swing'])
                stock_chance_map[sid]['ma5_exchange_portion'] = current_row['ma5_exchange_portion']
                if len(columns) == 0:
                    columns = stock_chance_map[sid].keys()

            except Exception as e:
                traceback.print_exc() 
                self.logger.exception("err=sort_with_dyn sid=%d", sid)
                continue

        #print stock_chance_map, columns
        if len(stock_chance_map) == 0:
            return None

        chance_df = pd.DataFrame(stock_chance_map.values(), index=stock_chance_map.keys(), columns = columns)
        #print chance_df
        result_df = chance_df.sort_values(by=['trend_strength', 'ma5_swing', 'count', 'vary_portion_strength', 'ma5_exchange_portion', 'ma5_vary_portion'], ascending=False)
        return result_df

    '''
    @desc 处理订单成交消息
    @param item dict(order_id, code, op, count, price, cost, time)
    @return None
    '''
    def fill_order(self, item):
        sid = self.portfolio.fill_order(item)

    # 输出持仓组合的盈收状况
    def finish(self):
        order_map = self.portfolio.get_portfolio(PortfolioManager.STATE_ALL)
        for sid, order_info in order_map.items():
            print format_log("order_info", order_info)
            records = self.portfolio.get_trade_records(sid)
            for record in records:
                print format_log("trade_record", record)

    '''
        @desc 获取股票当前最新价格/时间等信息, 用分钟级交易来比较更新daily_item中的time/close_price
        @param sid
        @return None/daily_item
    '''
    def get_stock_currentinfo(self, sid, day):
        info = dict()

        daily_cache_value = self.redis_conn.get("daily-"+ str(sid) + "-" + str(day))
        daily_item = json.loads(daily_cache_value) if daily_cache_value else None

        key = "rt-" + str(sid) + "-" + str(day)
        last_item = None
        if self.redis_conn.llen(key) > 0:
            last_item = json.loads(self.redis_conn.lindex(key, -1))

        if daily_item is None and last_item is None:
            self.logger.info("err=get_daily_item sid=%d day=%d", sid, day)
            return None
        elif daily_item:
            info = daily_item
            info['time'] = int(int(daily_item['time']) / 100)
        elif last_item:
            info = last_item

        if last_item and last_item['time'] > info['time']:
            info['close_price'] = last_item['price']
            info['time'] = last_item['time']

        return info
