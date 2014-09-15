package com.fox.stockcat.bean;

import org.json.JSONException;
import org.json.JSONObject;

// 股票池单个股票项
public class StockPoolItem {
	public int sid;
	public String scode;
	public String name;
	public int wave;
	public int startDay;
	public int contDays;
	public int score;
	public double currentPrice;
	public double priceVaryAmount;
	public double priceVaryPortion;
	public double volumeVaryPortion;
	
	public String toString() {
		return "StockPoolItem[sid=" + String.valueOf(sid) + " scode=" + scode + " name=" + name + " wave=" + String.valueOf(wave) 
				+ " start_day=" + String.valueOf(startDay) + " cont_days=" + String.valueOf(contDays)
				+ " score=" + String.valueOf(score) + " current_price=" + String.valueOf(currentPrice) 
				+ " price_vary_amount=" + String.valueOf(priceVaryAmount) + " price_vary_portion=" + String.valueOf(priceVaryPortion)
				+ " volume_vary_portion=" + String.valueOf(volumeVaryPortion) + "]";
	}
	
	// 根据json对象初始化
	public static StockPoolItem initWithJsonObject(JSONObject obj) {
		StockPoolItem poolItem = new StockPoolItem();
		
		try {
				poolItem.sid = obj.getInt("sid");
				if (obj.has("scode")) {
					poolItem.scode = obj.getString("scode");
				}
				
				poolItem.name = obj.getString("name");
				poolItem.wave = obj.getInt("wave");
				poolItem.startDay = obj.getInt("start_day");
				poolItem.contDays = obj.getInt("cont_days");
				poolItem.score = obj.getInt("score");
				poolItem.currentPrice = obj.getDouble("current_price");
				poolItem.priceVaryAmount = obj.getDouble("sum_price_vary_amount");
				poolItem.priceVaryPortion = obj.getDouble("sum_price_vary_portion");
				poolItem.volumeVaryPortion = obj.getDouble("max_volume_vary_portion");
    	} catch (JSONException e) {
    		System.out.println("err=init_stock_poolitem exception=" + e);
    	} finally {
    	}
		
		return poolItem;
	}
}
