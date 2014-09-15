package com.fox.stockcat.adapters;

import java.util.ArrayList;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;

import com.fox.stockcat.R;
import com.fox.stockcat.bean.StockPriceThreshold;
import com.fox.stockcat.util.FormatUtil;

public class PriceThresholdAdapter extends BaseAdapter {
	private LayoutInflater inflater;
	private Context context;
	private ListView listView;
	private ArrayList<StockPriceThreshold> itemlist;
	
	public PriceThresholdAdapter(Context context, ListView listView, ArrayList<StockPriceThreshold> itemlist) {
		super();
		
		this.context = context;
		this.listView = listView;
		this.inflater = LayoutInflater.from(context);
		this.itemlist = itemlist;
	}
	
	@Override
	public int getCount() {
		// TODO Auto-generated method stub
		return itemlist.size();
	}

	@Override
	public Object getItem(int position) {
		// TODO Auto-generated method stub
		return itemlist.get(position);
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
		StockPriceThreshold item = (StockPriceThreshold)getItem(position);
		
		if (null == convertView) {
			convertView = inflater.inflate(R.layout.price_item, null);
			holder = new ViewHolder();
			
			holder.name_tv = (TextView)convertView.findViewById(R.id.stock_name_tv);
			holder.code_tv = (TextView)convertView.findViewById(R.id.stock_code_tv);
			holder.close_price_tv = (TextView)convertView.findViewById(R.id.close_price_tv);
			holder.threshold_tv = (TextView)convertView.findViewById(R.id.threshold_tv);
			holder.trend_img = (ImageView)convertView.findViewById(R.id.trend_img);
			
			convertView.setTag(holder);
		} else {
			holder = (ViewHolder)convertView.getTag();
		}
		
		holder.name_tv.setText(item.name);
		holder.code_tv.setText("(" + item.scode + ")");
		holder.close_price_tv.setText(FormatUtil.formatPrice(item.price));
		
		boolean trend = item.isUpTrend();
		holder.threshold_tv.setText(FormatUtil.formatPriceThreshold(item.getThresholdType(), trend));		
		// 根据trend趋势设置对应图片
		holder.trend_img.setImageResource(trend? R.drawable.trend_up : R.drawable.trend_down);
		
		return convertView;
	}

	class ViewHolder {
		TextView name_tv;
		TextView code_tv;
		TextView close_price_tv;
		TextView threshold_tv;
		ImageView trend_img;
	}
}
