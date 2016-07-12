#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 公共接口
#date: 2013-09-16

import datetime, sys, time, json
#sys.path.append('../../../server')  
sys.path.append('../../../../server')  
from pyutil.util import Util, safestr, format_log
from pyutil.sqlutil import SqlUtil, SqlConn
import redis

# 假期定义
holidays = [{'start': 20160207, 'end': 20160213}, 20160404,
        20160502, 20160609, 20160610, 20160915, 20160916, {'start': 20151001, 'end':20151007}]
us_holidays = [20160216,20160403,20160525,20160703,20160907,20161126,20161225]
locations = {1:"cn", 2:"hk", 3:"us"}
# ecode
ecodes = {1:"sh", 2:"sz", 3:"hk", 4:"NASDAQ", 5:"NYSE"}

# 判断当天是否开市
def is_market_open(day, location = 1):
    current_time = datetime.datetime(int(day[0:4]), int(day[4:6]), int(day[6:8]))  
    weekday = current_time.weekday()
    #print day, weekday

    # 周六或周日休市
    if weekday == 5 or weekday == 6:
        return False

    intday = int(day)
    market_holidays = []
    if 1 == location:
        market_holidays = holidays
    elif 3 == location:
        market_holidays = us_holidays

    for item in market_holidays:
        if (isinstance(item, dict) and item['start'] <= intday and intday <= item['end']) or (intday == item):
            return False
    return True

'''
    @desc: 获取当前日期最近的第几个交易日
    @param current_day string
    @param offset int
    @param location int 缺省为1(china)
    @return in
'''
def get_past_openday(current_day, offset, location = 1):
    current_day = str(current_day)
    current_time = datetime.datetime(int(current_day[0:4]), int(current_day[4:6]), int(current_day[6:8]))  
    step = 1
    # 含当天
    open_count = 0

    while True:
        last_day = '{0:%Y%m%d}'.format(current_time + datetime.timedelta(days = -1 * step))
        step += 1
        if is_market_open(last_day, location):
            open_count += 1
            if open_count == offset:
                return last_day

    return 0

# 获取最近N日连续上涨/下跌的股票
def get_cont_stock(db_config, current_day, day_count, sum_portion, rise = True):
    start_day = get_past_openday(current_day, day_count)
    operator = ">" if rise else "<"

    sql = "select sid from t_stock_data where day >= {start_day} and day <= {current_day} and vary_portion {operator} 0 \
        group by sid having count(*) >= {day_count} and sum(vary_portion) >= {low_portion} and sum(vary_portion) <= {high_portion}"\
        .format(start_day=start_day, current_day=current_day, operator=operator, day_count=day_count, \
                low_portion=sum_portion[0], high_portion=sum_portion[1])
    print sql

    record_list = []

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    return [item['sid'] for item in record_list]

# 获取所有股票列表, 包含指数
def get_stock_list(db_config, type = 0, location = 1):
    sql = "select id, code, name, type, pinyin, ecode, location, alias, company, business, capital, out_capital, profit, assets, dividend, hist_high, hist_low, year_high, year_low, month6_high, \
            month6_low, month3_high, month3_low from t_stock where status = 'Y' "
    if type > 0:
        sql = sql + " and type = " + str(type)
    if location > 0:
        sql = sql + " and location = " + str(location)

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    stock_list = dict()
    for stock_info in record_list:
        stock_list[int(stock_info['id'])] = stock_info

    return stock_list

# 获取股票信息
def get_stock_info(db_config, sid):
    sql = "select id, code, name, type, pinyin, ecode, location, alias, company, business, capital, out_capital, profit, assets, dividend, hist_high, hist_low, year_high, year_low, month6_high, \
            month6_low, month3_high, month3_low from t_stock where status = 'Y' and id = " + str(sid)
    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    stock_info = dict()
    if len(record_list) > 0:
        stock_info = record_list[0]
    return stock_info

'''
  @desc: 获取指定日期股票的总览数据
  @param db_config dict DB配置
  @param day int
  @param sid int 股票id, 缺省0表示获取所有股票
  @return dict()
'''
def get_stock_data(db_config, day, sid=0):
    if sid == 0:
        sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
            vary_price, vary_portion from t_stock_data where day = {day} and status = 'Y'".format(day=day)
    else:
        if isinstance(sid, int):
            sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
            vary_price, vary_portion from t_stock_data where day = {day} and sid = {sid} and status = 'Y'".format(day=day, sid=sid)
        elif isinstance(sid, list):
            sql = "select sid, day, open_price, high_price, low_price, close_price, volume, amount, \
            vary_price, vary_portion from t_stock_data where day = {day} and sid in ({sid_list}) and status = 'Y'".format(day=day, sid=",".join(sid))
    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    data = dict()
    for stock_data in record_list:
        data[int(stock_data['sid'])] = stock_data

    return data if 0 == sid else data[sid]
'''
    @desc: 添加股票池记录
    @param db_config dict
    @param redis_config dict
    @param sid int
    @param day int
    @param source int
    @param trend_info dict
    @return bool
'''
def add_stock_pool(db_config, redis_config, sid, day, source, trend_info = dict()):
    sql = "select id, sid, day, source from t_stock_pool where sid = {sid} and day = {day} and status = 'Y'".format(sid=sid, day=day)
    
    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)

        if len(record_list) > 0:
            record_id = record_list[0]['id']
            new_source = int(record_list[0]['source']) | source
            oper_sql = "update t_stock_pool set source = {source} where id = {id}".format(source=new_source, id=record_id)
        else:
            fields = {'sid': sid, 'day': day, 'source': source, 'status': 'Y'}
            fields['create_time'] = time.mktime( datetime.datetime.now().timetuple() )
            if trend_info:
                fields.update(trend_info)

            # 获取当日行情数据
            hqdata = get_hqdata(db_config, redis_config, sid, day)
            if hqdata:
                fields['close_price'] = hqdata['daily']['close_price']
                if 'policy' in hqdata and hqdata['policy']:
                    fields['volume_ratio'] = round(float(hqdata['policy']['volume_ratio']), 2)
                    fields['rise_factor'] = round(float(hqdata['policy']['rise_factor']), 2)
            #print fields

            oper_sql = SqlUtil.create_insert_sql("t_stock_pool", fields)

        db_conn.query_sql(oper_sql, True)
    except Exception as e:
        print e
        return False

    return True

'''
  @desc: 添加股票阶段高点/低点记录
        TODO: 暂时没有判断股票突破某个高点(低点)持续上涨(下跌), 导致每天连续有记录, 此时只需要记录第1天, 后面如果高/低点类型不同再记录
  @param db_config dict DB配置
  @param sid int
  @param day int 
  @param price float
  @param high_type 高点类型
  @param low_type 低点类型
  @return bool
'''
def add_stock_price_threshold(db_config, sid, day, price, high_type, low_type):
    info = {'sid': sid, 'day': day, 'price': price, 'status': 'Y'}
    info['create_time'] = time.mktime( datetime.datetime.now().timetuple() )
    info['high_type'] = high_type
    info['low_type'] = low_type
    
    sql = SqlUtil.create_insert_sql("t_stock_price_threshold", info)
    print sql
    try:
        db_conn = SqlUtil.get_db(db_config)
        db_conn.query_sql(sql, True)
    except Exception as e:
        print e
        return False
  
    return True

'''
  @desc: 获取股票指定日期范围内的价格突破记录
  @param: db_config dict
  @param: sid int
  @param: start_day int
  @param: end_day int
  @param: high_type 高点类型
  @param: low_type 低点类型
  @return list
'''
def get_stock_price_threshold(db_config, sid, start_day, end_day, high_type, low_type):
    sql = "select sid, day, price, low_type, high_type from t_stock_price_threshold where status = 'Y' and sid = {sid} and day >= {start_day} and day <= {end_day}"\
            .format(sid=sid, start_day=start_day, end_day=end_day)
    if high_type > 0:
        sql = sql + " and high_type = " + str(high_type)
    if low_type > 0:
        sql = sql + " and low_type = " + str(low_type)

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    return record_list
    
'''
  @desc: 获取股票指定日期范围内的趋势列表
  @param: db_config dict
  @param: sid int
  @param: start_day int
  @param: end_day int
  @return list
'''
def get_stock_trendlist(db_config, sid, start_day, end_day):
    sql = "select sid, type, start_day, end_day, count, high_day, high, low_day, low, start_value, end_value, vary_portion, trend, shave \
            from t_stock_trend where status = 'Y' and sid = {sid} and ((start_day >= {start_day} and start_day <= {end_day}) \
            or (end_day >= {start_day} and end_day <= {end_day})) order by start_day asc".format(sid=sid, start_day=start_day, end_day=end_day)
    print sql

    try:
        db_conn = SqlUtil.get_db(db_config)
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None

    return record_list

'''
  @desc: 预估当天的成交量
  @param cur_volume 当天的成交量
  @param cur_time 当天的时刻
  @return int
'''
def get_predict_volume(cur_volume, cur_time, location):
    hour = int(cur_time[0:2])
    min = int(cur_time[2:4])
    #print hour, min

    daily_min = min
    if 1 == location:
        if hour >= 15:
            return cur_volume

        if hour >= 9 and hour <= 11:
            daily_min += (hour - 9) * 60 - 30
        elif hour >= 13 and hour < 15:
            daily_min += 120 + (hour - 13) * 60
    	return round(cur_volume * 240 / daily_min)
    else:
        curmin = (hour - 9) * 60 - 30
    return round(cur_volume * 390 / curmin)

'''
    @desc: 获取过去几天的总览数据, 目前暂定过去5天, 先从缓存加载, 数据字段包括:
            平均成交量(avg_volume)/累计涨幅(sum_vary_portion)/累计涨跌额(sum_vary_price)/平均价格(avg_price)/最高价(high_price)/最低价(low_price)
    @param: db_config dict
    @param: cur_day int 当前日期
    @param: count int 过去的天数
    @return dict (sid -> past_data) sid为string
'''
def get_past_data(db_config, redis_config, cur_day, count):
    key = "pastdata-" + str(cur_day)
    stock_datamap = dict()
    redis_conn = redis.StrictRedis(redis_config['host'], redis_config['port'])

    datamap = redis_conn.hgetall(key)
    # 从redis中取出来的key都是string
    if datamap:
        return datamap

    db_conn = SqlUtil.get_db(db_config)
    start_day = get_past_openday(str(cur_day), count)

    try:
        sql = "select sid, avg(volume) as avg_volume, sum(vary_price) as sum_vary_price, sum(vary_portion) as sum_vary_portion, \
        avg(close_price) as avg_close_price, max(high_price) as high_price, min(low_price) as low_price from t_stock_data \
        where day >= " + str(start_day) + " and day < " + str(cur_day) + " group by sid"
        #print sql
        record_list = db_conn.query_sql(sql)
    except Exception as e:
        print e
        return None
    
    for record in record_list:
        item = dict()
        sid = record['sid']

        item['sid'] = int(sid)
        item['avg_volume'] = round(float(record['avg_volume']))
        item['sum_vary_price'] = float(record['sum_vary_price'])
        item['sum_vary_portion'] = float(record['sum_vary_portion'])
        item['avg_close_price'] = float(record['avg_close_price'])
        item['high_price'] = float(record['high_price'])
        item['low_price'] = float(record['low_price'])

        stock_datamap[sid] = json.dumps(item)
    #print stock_datamap

    redis_conn.hmset(key, stock_datamap)
    return stock_datamap

'''
    @desc: 计算HHMMSS格式时间数值之间的差异, 返回差额的秒(t1 >= t2)
    @param: t1 int HHMMSS
    @param: t2 int
    @return interval
'''
def time_diff(t1, t2):
    hour_diff = int(t1/10000) - int(t2/10000)
    min_diff = int(t1%10000/100) - int(t2%10000/100)
    second_diff = int(t1%100) - int(t2%100)

    return hour_diff * 3600 + min_diff * 60 + second_diff

'''
    @desc: 计算2个时间值之间的交易时间间隔, 返回差额的秒(now_time >= past_time)
    @param: now_time int HHMMSS
    @param: past_time int
    @return interval
'''
def market_time_diff(now_time, past_time):
    if past_time < 93000:
        past_time = 93000
    if now_time >= 153400:
        now_time = 153400
    
    if past_time >= 130000 or now_time <= 113000:
        return time_diff(now_time, past_time)
    else:
        return time_diff(113000, past_time) + time_diff(now_time, 130000)

'''
    @desc: 获取股票调用代码
    @param: code string
    @param: ecode int
    @param: location int
    @return string
'''
def get_scode(code, ecode, location):
    if 1 == location or 2 == location:
        return ecodes[ecode] + code
    elif 3 == location:
        return "us" + code

'''
    @desc: 获取当前时刻
    @param: location int
    @return int
'''
def get_timenumber(location = 1):
    if 1 == location or 2 == location:
        cur_time = datetime.datetime.now().time()
    elif 3 == location:
        # TODO: 判断是夏令时(12)还是冬令时(13)
        us_time = datetime.datetime.now() - datetime.timedelta(hours = 12)
        cur_time = us_time.time()
        
    return cur_time.hour * 10000 + cur_time.minute * 100 + cur_time.second

'''
    @desc: 获取股票当日行情数据
    @param: db_config dict
    @param: redis_config dict
    @param: sid int
    @param: day int
    @return dict('daily', 'policy')
'''
def get_hqdata(db_config, redis_config, sid, day):
    hqdata = dict()
    redis_conn = redis.StrictRedis(redis_config['host'], redis_config['port'])
    
    daily_key = "daily-" + str(sid) + "-" + str(day)
    cache_value = redis_conn.get(daily_key)
    if cache_value:
        hqdata['daily'] = json.loads(cache_value)
        hqdata['policy'] = redis_conn.hgetall("daily-policy-" + str(sid) + "-" + str(day))
    else:
        hqdata['daily'] = get_stock_data(db_config, sid, day)

    return hqdata

# 返回location对应的名称
def get_location_name(location):
    if location in locations:
        return locations[location]
    else:
        return "cn"

# 返回location对应的当前日期
def get_current_day(location):
    if 3 == location:
        return int("{0:%Y%m%d}".format(datetime.date.today() - datetime.timedelta(hours = 12)))
    else:
        return int("{0:%Y%m%d}".format(datetime.date.today()))    
        
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <day> <config>"
        sys.exit(1)

    day = sys.argv[1]
    print get_past_openday(day, 1)
    print get_past_openday(day, 2)

    print time_diff(94310, 92500)
    print time_diff(142500, 93530)
    print market_time_diff(931, 925)

    print time.time()

    config_info = Util.load_config(sys.argv[2])        
    db_config = config_info['DB']
    db_config['port'] = int(db_config['port'])

    stock_info = get_stock_info(db_config, 9604)
    print stock_info

    redis_config = config_info['REDIS']
    redis_config['port'] = int(redis_config['port'])
    print db_config, redis_config

    print get_current_day(3)

    '''
    day = int(day)
    hqdata = get_hqdata(db_config, redis_config, 116, day)
    print hqdata

    try:
        add_stock_pool(db_config, redis_config, 116, day, 2, {'trend':3, 'wave':3}) 
    except Exception as e:
        print e
    '''
