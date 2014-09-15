package com.fox.stockcat;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import android.annotation.SuppressLint;
import android.app.Fragment;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.ListView;
import android.widget.Toast;

import com.fox.stockcat.adapters.PriceThresholdAdapter;
import com.fox.stockcat.bean.StockPriceThreshold;
import com.fox.stockcat.util.ConfigHelper;
import com.loopj.android.http.AsyncHttpClient;
import com.loopj.android.http.JsonHttpResponseHandler;

@SuppressLint("NewApi")
public class PriceFragment extends Fragment {	
	private ListView listView;
	private ArrayList<StockPriceThreshold> itemlist;
	private PriceThresholdAdapter adapter;
		
	@Override
	public View onCreateView(LayoutInflater inflater, ViewGroup container,
			Bundle savedInstanceState) {
		// TODO Auto-generated method stub
		super.onCreateView(inflater, container, savedInstanceState);
		return inflater.inflate(R.layout.price_threshold, container, false);
	}
	
	@Override
	public void onViewCreated(View view, Bundle savedInstanceState) {
		// TODO Auto-generated method stub
		super.onViewCreated(view, savedInstanceState);
		initialize();
	}
	
	// 初始化
	public void initialize() {
		itemlist = new ArrayList<StockPriceThreshold>();
		itemlist.clear();
		listView = (ListView)getActivity().findViewById(R.id.price_list_lv);
			
		adapter = new PriceThresholdAdapter(getActivity(), listView, itemlist);
		listView.setAdapter(adapter);
				
		listView.setOnItemClickListener(new OnItemClickListener() {
			@Override
			public void onItemClick(AdapterView<?> parent, View view,
					int position, long id) {
				// TODO Auto-generated method stub
				StockPriceThreshold item = (StockPriceThreshold)listView.getAdapter().getItem(position);
				Toast.makeText(getActivity(), "sid=" + item.sid + " name=" + item.name, Toast.LENGTH_SHORT).show(); 
				// 启动StockActivity	
			}
		});			
		
		getData(ConfigHelper.getDay(17), "all");
	}
	
	public void getData(int day, String type) {
		Map<String, String> varMap = new HashMap<String, String>();
		varMap.put("day", String.valueOf(day));
		varMap.put("type", type);
		
		AsyncHttpClient client = new AsyncHttpClient();
		client.get(ConfigHelper.getFormatUrl(ConfigHelper.PRICE_THRESHOLD_API_URL, varMap), new JsonHttpResponseHandler() {
		    @Override
		    public void onSuccess(JSONObject response) {
		    	try {
		    		Log.v("JSON", response.toString());
		    		itemlist.clear();
		    		
			        JSONArray data = response.getJSONArray("data");
			        for (int i = 0; i < data.length(); i++) {
			        	JSONObject element = data.getJSONObject(i);
			        	StockPriceThreshold item = StockPriceThreshold.initWithJsonObject(element.getJSONObject("threshold"));
			        	
			        	JSONObject stockObj = element.getJSONObject("stock");
			        	item.scode = stockObj.getString("code");
			        	item.name = stockObj.getString("name");
			        	
			        	itemlist.add(item);
			        	// Log.v("JSON", item.toString());
			        }
		    	} catch (JSONException e) {
		    		System.out.println("json parse exception" + e);
		    	} finally {
		    		
		    		System.out.println("desc=get_price_threshold item_count=" + String.valueOf(itemlist.size()));
		    		adapter.notifyDataSetChanged();
		    		// System.out.println("desc=adapter_data_count total=" + String.valueOf(adapter.getCount()));
		    	}
		    	
		    }
		    
		});
	}
}
