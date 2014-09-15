package com.fox.stockcat;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import android.os.Bundle;
import android.annotation.SuppressLint;
import android.app.Fragment;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.ListView;
import android.widget.Toast;

import com.fox.stockcat.adapters.PoolListAdapter;
import com.fox.stockcat.bean.StockPoolItem;
import com.fox.stockcat.util.ConfigHelper;
import com.loopj.android.http.AsyncHttpClient;
import com.loopj.android.http.JsonHttpResponseHandler;

@SuppressLint("NewApi")
public class PoolFragment extends Fragment {
	private ListView listView;
	private PoolListAdapter adapter;
	protected ArrayList<StockPoolItem> poolItemList;
	
	@Override
	public View onCreateView(LayoutInflater inflater, ViewGroup container,
			Bundle savedInstanceState) {
		// TODO Auto-generated method stub
		super.onCreateView(inflater, container, savedInstanceState);
		return inflater.inflate(R.layout.stock_pool, container, false);
	}
	
	@Override
	public void onViewCreated(View view, Bundle savedInstanceState) {
		// TODO Auto-generated method stub
		super.onViewCreated(view, savedInstanceState);
		initialize();
	}
	
	// 初始化
	private void initialize() {
		poolItemList = new ArrayList<StockPoolItem>();
		poolItemList.clear();
		listView = (ListView)getActivity().findViewById(R.id.pool_list_lv);
			
		adapter = new PoolListAdapter(getActivity(), listView, poolItemList);
		listView.setAdapter(adapter);
				
		listView.setOnItemClickListener(new OnItemClickListener() {
			@Override
			public void onItemClick(AdapterView<?> parent, View view,
					int position, long id) {
				// TODO Auto-generated method stub
				StockPoolItem poolItem = (StockPoolItem)listView.getAdapter().getItem(position);
				Toast.makeText(getActivity(), "sid=" + poolItem.sid + " name=" + poolItem.name, Toast.LENGTH_SHORT).show(); 
				// 启动StockActivity	
			}
		});			
		
		getPoolList(ConfigHelper.getDay(17), ConfigHelper.MIN_CONT_DAYS, 3);
		
	}
	
	// 向服务器端拉取数据
	public void getPoolList(int day, int contDay, int wave) {
		Map<String, String> varMap = new HashMap<String, String>();
		varMap.put("day", String.valueOf(day));
		varMap.put("cont_day", String.valueOf(contDay));
		varMap.put("wave", String.valueOf(wave));
		
		AsyncHttpClient client = new AsyncHttpClient();
		client.get(ConfigHelper.getFormatUrl(ConfigHelper.POOL_API_URL, varMap), new JsonHttpResponseHandler() {
		    @Override
		    public void onSuccess(JSONObject response) {
		    	try {
		    		Log.v("JSON", response.toString());
		    		poolItemList.clear();
		    		
			        JSONArray data = response.getJSONArray("data");
			        for (int i = 0; i < data.length(); i++) {
			        	JSONObject element = data.getJSONObject(i);
			        	StockPoolItem item = StockPoolItem.initWithJsonObject(element.getJSONObject("item"));
			        	
			        	JSONObject stockObj = element.getJSONObject("stock");
			        	item.scode = stockObj.getString("code");
			        	
			        	poolItemList.add(item);
			        	// Log.v("JSON", item.toString());
			        }
		    	} catch (JSONException e) {
		    		System.out.println("json parse exception" + e);
		    	} finally {
		    		
		    		System.out.println("desc=get_pool_list item_count=" + String.valueOf(poolItemList.size()));
		    		// adapter.refresh(poolItemList);
		    		adapter.notifyDataSetChanged();
		    		// System.out.println("desc=adapter_data_count total=" + String.valueOf(adapter.getCount()));
		    	}
		    	
		    }
		});
	}
}
