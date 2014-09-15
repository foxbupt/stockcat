package com.fox.stockcat;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import android.annotation.SuppressLint;
import android.os.Bundle;
import android.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.ListView;
import android.widget.Toast;

import com.fox.stockcat.adapters.RealtimeAdapter;
import com.fox.stockcat.bean.RealtimeItem;
import com.fox.stockcat.util.ConfigHelper;
import com.loopj.android.http.AsyncHttpClient;
import com.loopj.android.http.JsonHttpResponseHandler;

@SuppressLint("NewApi")
public class RealtimeFragment extends Fragment {
	private ListView listView;
	private RealtimeAdapter adapter;
	protected ArrayList<RealtimeItem> itemList;
	
	@Override
	public View onCreateView(LayoutInflater inflater, ViewGroup container,
			Bundle savedInstanceState) {
		// TODO Auto-generated method stub
		super.onCreateView(inflater, container, savedInstanceState);
		return inflater.inflate(R.layout.realtime_list, container, false);
	}
	
	
	@Override
	public void onViewCreated(View view, Bundle savedInstanceState) {
		// TODO Auto-generated method stub
		super.onViewCreated(view, savedInstanceState);
		initialize();
	}
	
	// 初始化
	private void initialize() {
		itemList = new ArrayList<RealtimeItem>();
		itemList.clear();
		listView = (ListView)getActivity().findViewById(R.id.realtime_list_lv);
			
		adapter = new RealtimeAdapter(getActivity(), listView, itemList);
		listView.setAdapter(adapter);
				
		listView.setOnItemClickListener(new OnItemClickListener() {
			@Override
			public void onItemClick(AdapterView<?> parent, View view,
					int position, long id) {
				// TODO Auto-generated method stub
				RealtimeItem item = (RealtimeItem)listView.getAdapter().getItem(position);
				Toast.makeText(getActivity(), "sid=" + item.sid + " name=" + item.name, Toast.LENGTH_SHORT).show(); 
				// 启动StockActivity	
			}
		});			
		
		getData(ConfigHelper.getDay(9), 3.0, 2.0);
		
	}
	
	// 向服务器端拉取数据
	public void getData(int day, double riseFactor, double volumeRatio) {
		Map<String, String> varMap = new HashMap<String, String>();
		varMap.put("day", String.valueOf(day));
		varMap.put("rf", String.valueOf(riseFactor));
		varMap.put("ratio", String.valueOf(volumeRatio));
		
		AsyncHttpClient client = new AsyncHttpClient();
		client.get(ConfigHelper.getFormatUrl(ConfigHelper.REALTIME_API_URL, varMap), new JsonHttpResponseHandler() {
		    @Override
		    public void onSuccess(JSONObject response) {
		    	try {
		    		Log.v("JSON", response.toString());
		    		itemList.clear();
		    		
			        JSONArray data = response.getJSONArray("data");
			        for (int i = 0; i < data.length(); i++) {
			        	JSONObject element = data.getJSONObject(i);
			        	RealtimeItem item = new RealtimeItem();
			        	
			        	item.sid = element.getInt("sid");
			        	item.riseFactor = element.getDouble("rf");
			        	
			        	JSONObject dailyObj = element.getJSONObject("daily");
			        	item.name = dailyObj.getString("name");			        	
			        	item.scode = dailyObj.getString("code");
			        	item.price = dailyObj.getDouble("close_price");
			        	item.lastClosePrice = dailyObj.getDouble("last_close_price");
			        	item.varyPortion = dailyObj.getDouble("vary_portion");
			        	
			        	JSONObject dailyPolicyObj = element.getJSONObject("daily_policy");
			        	item.volumeRatio = dailyPolicyObj.getDouble("volume_ratio");
			        	item.highPortion = dailyPolicyObj.getDouble("high_portion");
			        	
			        	itemList.add(item);
			        	Log.v("JSON", item.toString());
			        }
		    	} catch (JSONException e) {
		    		System.out.println("json parse exception" + e);
		    	} finally {
		    		
		    		System.out.println("desc=get_data item_count=" + String.valueOf(itemList.size()));
		    		// adapter.refresh(poolItemList);
		    		adapter.notifyDataSetChanged();
		    		// System.out.println("desc=adapter_data_count total=" + String.valueOf(adapter.getCount()));
		    	}
		    	
		    }
		});
	}
}
