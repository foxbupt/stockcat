package com.fox.stockcat.bean;

import org.json.JSONException;
import org.json.JSONObject;

// 股票突破记录
public class StockPriceThreshold {
	public int sid;
	public String scode;
	public String name;
	public int day;
	public double price;
	public int highType;
	public int lowType;
	
	public String toString() {
		return "StockPriceThreshold[sid=" + String.valueOf(sid) + " scode=" + scode + " name=" + name 
				+ " day=" + String.valueOf(day) + " price=" + String.valueOf(price) 
				+ " high_type=" + String.valueOf(highType) + " low_type=" + String.valueOf(lowType) + "]";
	}
	
	// 获取价格突破类型
	public int getThresholdType() {
		return (highType > 0)? highType : lowType;
	}
	
	// 判断突破是上涨还是下跌, 上涨返回true, 下跌返回false
	public boolean isUpTrend() {
		return (highType > 0)? true : false;
	}
	
	// 根据json对象初始化
	public static StockPriceThreshold initWithJsonObject(JSONObject obj) {
		StockPriceThreshold item = new StockPriceThreshold();
		
		try {
				item.sid = obj.getInt("sid");
				if (obj.has("scode")) {
					item.scode = obj.getString("scode");
				}
				
				if (obj.has("name")) {
					item.name = obj.getString("name");
				}
				
				item.day = obj.getInt("day");
				item.price = obj.getDouble("price");
				item.highType = obj.getInt("high_type");
				item.lowType = obj.getInt("low_type");
    	} catch (JSONException e) {
    		System.out.println("err=init_stock_pricethreshold exception=" + e);
    	} finally {
    	}
		
		return item;
	}
}
