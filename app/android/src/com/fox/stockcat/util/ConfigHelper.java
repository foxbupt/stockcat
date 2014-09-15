package com.fox.stockcat.util;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Map;

// 全局配置辅助类
public class ConfigHelper {
//	public static final String POOL_API_URL = "http://cat.fox.com/stock/api/poollist?day={day}&cont_day={cont_day}&wave={wave}";
	public static final String POOL_API_URL = "http://www.bencaimao.com/stock/api/poollist?day={day}&cont_day={cont_day}&wave={wave}";
	public static final String PRICE_THRESHOLD_API_URL = "http://www.bencaimao.com/stock/api/price?day={day}&type={type}";
	public static final String REALTIME_API_URL = "http://www.bencaimao.com/stock/api/realtime?day={day}&rf={rf}&ratio={ratio}";
	
	// 最小连续上涨天数
	public static final int MIN_CONT_DAYS = 3;
	
	// 获取格式化后的访问url
	public static String getFormatUrl(String template, Map<String, String> varMap) {
		String url = template;
		
		for (String key : varMap.keySet()) {
			url = url.replace("{" + key + "}", varMap.get(key));
		}
		
		return url;
	}
	
	// 获取前一个开市的交易日
	public static int getLastOpenDay() {
		Calendar c = Calendar.getInstance();
		int weekday = c.get(Calendar.DAY_OF_WEEK);
		int offset = 0;
		
		// 周末取上周五的数据
		if ((weekday == Calendar.SATURDAY) || (weekday == Calendar.SUNDAY)) {
			offset = weekday - Calendar.FRIDAY;
		}  
		else {
			offset = (weekday == Calendar.MONDAY)? 3 : 1;
		}
		
		if (offset > 0) {
			c.add(Calendar.DAY_OF_MONTH, -1 * offset);
		}
		
		SimpleDateFormat df = new SimpleDateFormat("yyyyMMdd");
		return Integer.parseInt(df.format(c.getTime()));
	}
	
	// 获取查看数据的日期, <= hour获取前一天
	public static int getDay(int pointHour) {
		Calendar c = Calendar.getInstance();
		int hour = c.get(Calendar.HOUR_OF_DAY);
		int weekday = c.get(Calendar.DAY_OF_WEEK);
		int offset = 0;
		
		// 周末取上周五的数据
		if ((weekday == Calendar.SATURDAY) || (weekday == Calendar.SUNDAY)) {
			offset = weekday - Calendar.FRIDAY;
		}  // 周中每天晚上5点前默认读取前一天的数据
		else if (hour <= pointHour) {
			offset = (weekday == Calendar.MONDAY)? 3 : 1;
		}
		
		if (offset > 0) {
			c.add(Calendar.DAY_OF_MONTH, -1 * offset);
		}
		
		SimpleDateFormat df = new SimpleDateFormat("yyyyMMdd");
		return Integer.parseInt(df.format(c.getTime()));
	}
}
