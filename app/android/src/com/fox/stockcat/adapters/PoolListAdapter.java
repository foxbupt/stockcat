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
import com.fox.stockcat.bean.StockPoolItem;
import com.fox.stockcat.util.FormatUtil;

public class PoolListAdapter extends BaseAdapter {
	private LayoutInflater inflater;
	private Context context;
	private ListView listView;
	private ArrayList<StockPoolItem> poolItemList;
	
	public PoolListAdapter(Context context, ListView listView, ArrayList<StockPoolItem> itemList) {
		super();
		
		this.context = context;
		this.listView = listView;
		this.inflater = LayoutInflater.from(context);
		this.poolItemList = itemList;
	}
	
	public void refresh(ArrayList<StockPoolItem> itemList) {
		notifyDataSetChanged();
	}
	
	@Override
	public int getCount() {
		// TODO Auto-generated method stub
		// Log.v("ADAPTER", "list_count=" + poolItemList.size());
		return poolItemList.size();
	}

	@Override
	public Object getItem(int position) {
		// TODO Auto-generated method stub
		return poolItemList.get(position);
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
		StockPoolItem item = (StockPoolItem)getItem(position);
		
		if (null == convertView) {
			convertView = inflater.inflate(R.layout.pool_stock_item, null);
			holder = new ViewHolder();
			
			holder.name_tv = (TextView)convertView.findViewById(R.id.stock_name_tv);
			holder.code_tv = (TextView)convertView.findViewById(R.id.stock_code_tv);
			holder.cont_day_tv = (TextView)convertView.findViewById(R.id.cont_days_tv);
			holder.current_price_tv = (TextView)convertView.findViewById(R.id.current_price_tv);
			holder.price_vary_tv = (TextView)convertView.findViewById(R.id.price_vary_tv);
			
			convertView.setTag(holder);
		} else {
			holder = (ViewHolder)convertView.getTag();
		}
		
		holder.name_tv.setText(item.name);
		holder.code_tv.setText("(" + item.scode + ")");
		holder.cont_day_tv.setText(String.valueOf(item.contDays));
		holder.current_price_tv.setText(FormatUtil.formatPrice(item.currentPrice));
		holder.price_vary_tv.setText(FormatUtil.formatPortion(item.priceVaryPortion));
		if (item.priceVaryPortion > 0) {
			holder.price_vary_tv.setTextColor(Color.RED);
		}
		
		return convertView;
	}
	
	class ViewHolder {
		TextView name_tv;
		TextView code_tv;
		TextView cont_day_tv;
		TextView current_price_tv;
		TextView price_vary_tv;
	}

}
