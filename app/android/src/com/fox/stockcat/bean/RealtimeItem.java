package com.fox.stockcat.bean;

// 实时上涨数据项
public class RealtimeItem {
	public int sid;
	public String scode;
	public String name;
	
	// 上涨因子
	public double riseFactor;
	// 当前价格
	public double price;
	// 昨收价格
	public double lastClosePrice;
	// 当前涨幅
	public double varyPortion;
	// 量比
	public double volumeRatio;
	// 最高价比例
	public double highPortion;
	
	public String toString() {
		return "RealtimeItem[sid=" + sid + " scode=" + scode + " name=" + name 
				+ " rise_factor=" + riseFactor + " price=" + price + " last_close_price=" + lastClosePrice
				+ " vary_portion=" + varyPortion + " volume_ratio=" + volumeRatio + " high_portion=" + highPortion + "]";
	}
	
}
