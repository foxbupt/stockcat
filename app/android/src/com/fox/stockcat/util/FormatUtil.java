package com.fox.stockcat.util;

// 格式化辅助类
public class FormatUtil {
	// 价格突破高/低点描述
	public static final String[] highTexts = {"历史最高", "年内最高", "60日最高", "30日最高"};
	public static final String[] lowTexts = {"历史最低", "年内最低", "60日最低", "30日最低"};
	
	public static String formatPrice(double price) {
		return String.format("%.2f", price);
	}
	
	public static String formatPortion(double portion) {
		String signedStr = (portion >= 0.0)? "" : "-"; 
		return String.format("%s%.2f%%", signedStr, portion);
	}
	
	// 格式化突破类型
	public static String formatPriceThreshold(int type, boolean trend) {
		int index = type - 1;
		return trend? highTexts[index] : lowTexts[index];
	}
	
}
