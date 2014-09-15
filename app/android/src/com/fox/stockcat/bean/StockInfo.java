package com.fox.stockcat.bean;

import java.util.Iterator;
import java.util.Map;

import org.json.JSONException;
import org.json.JSONObject;

// 股票基本信息
public class StockInfo {
	public int sid;
	public String name;
	public String code;
	public String ecode;
	public Map<String, String> fieldMap;
	
	@Override
	public String toString() {
		// TODO Auto-generated method stub
		String info = "StockInfo[sid=" + String.valueOf(sid) + " name=" + name + " code=" + code + " ecode=" + ecode;
		for (String key : fieldMap.keySet()) {
			info += " " + key + "=" + fieldMap.get(key);
		}
		
		info += "]";
		return info;
	}	
	
	// 根据json对象构造
	public static StockInfo initWithJsonObject(JSONObject obj) {
		StockInfo item = new StockInfo();
		
		try {
				item.sid = obj.getInt("id");
				item.name = obj.getString("name");
				item.code = obj.getString("code");
				item.ecode = obj.getString("ecode");
				
				Iterator itr = obj.keys();
				while (itr.hasNext()) {
					String key = (String)itr.next();
					if (key.equals("id") || key.equals("name") || key.equals("code") || key.equals("ecode")) {
						continue;
					}
					
					item.fieldMap.put(key, obj.getString(key));
				}
    	} catch (JSONException e) {
    		System.out.println("err=init_stock_json exception=" + e);
    	} finally {
    	}
		
		return item;
	}
}
