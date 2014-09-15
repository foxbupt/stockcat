package com.fox.stockcat.adapters;

import java.util.ArrayList;

import android.content.Context;
import android.graphics.Color;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.ListView;
import android.widget.TextView;

import com.fox.stockcat.R;
import com.fox.stockcat.bean.RealtimeItem;
import com.fox.stockcat.util.FormatUtil;

public class RealtimeAdapter extends BaseAdapter {
	private LayoutInflater inflater;
	private Context context;
	private ListView listView;
	private ArrayList<RealtimeItem> itemList;
	
	public RealtimeAdapter(Context context, ListView listView, ArrayList<RealtimeItem> itemList) {
		super();
		
		this.context = context;
		this.listView = listView;
		this.inflater = LayoutInflater.from(context);
		this.itemList = itemList;
	}
	
	public void refresh(ArrayList<RealtimeItem> itemList) {
		notifyDataSetChanged();
	}
	
	@Override
	public int getCount() {
		// TODO Auto-generated method stub
		// Log.v("ADAPTER", "list_count=" + poolItemList.size());
		return itemList.size();
	}

	@Override
	public Object getItem(int position) {
		// TODO Auto-generated method stub
		return itemList.get(position);
	}

	@Override
	public long getItemId(int position) {
		// TODO Auto-generated method stub
		return position;
	}

	@Override
	public View getView(int position, View convertView, ViewGroup parent) {
		// TODO Auto-generated method stub
		ViewHolder holder;
		RealtimeItem item = (RealtimeItem)getItem(position);
		
		if (null == convertView) {
			convertView = inflater.inflate(R.layout.realtime_item, null);
			holder = new ViewHolder();
			
			holder.name_tv = (TextView)convertView.findViewById(R.id.stock_name_tv);
			holder.code_tv = (TextView)convertView.findViewById(R.id.stock_code_tv);
			holder.close_price_tv = (TextView)convertView.findViewById(R.id.close_price_tv);
			holder.vary_portion_tv = (TextView)convertView.findViewById(R.id.vary_portion_tv);
			holder.volume_ratio_tv = (TextView)convertView.findViewById(R.id.volume_ratio_tv);
			holder.high_portion_tv = (TextView)convertView.findViewById(R.id.high_portion_tv);
			
			convertView.setTag(holder);
		} else {
			holder = (ViewHolder)convertView.getTag();
		}
		
		holder.name_tv.setText(item.name);
		holder.code_tv.setText("(" + item.scode + ")");
		holder.close_price_tv.setText(FormatUtil.formatPrice(item.price));
		holder.volume_ratio_tv.setText(FormatUtil.formatPrice(item.volumeRatio));
		holder.vary_portion_tv.setText(FormatUtil.formatPortion(item.varyPortion));
		holder.high_portion_tv.setText(FormatUtil.formatPortion(item.highPortion));
		
		return convertView;
	}
	
	class ViewHolder {
		TextView name_tv;
		TextView code_tv;
		TextView close_price_tv;
		TextView vary_portion_tv;
		TextView volume_ratio_tv;
		TextView high_portion_tv;
	}

}
