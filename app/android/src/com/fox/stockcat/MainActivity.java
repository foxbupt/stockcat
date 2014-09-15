package com.fox.stockcat;

import java.util.HashMap;

import com.fox.stockcat.util.ConfigHelper;

import android.annotation.SuppressLint;
import android.app.Fragment;
import android.app.FragmentManager;
import android.app.FragmentTransaction;
import android.os.Bundle;
import android.support.v4.app.FragmentActivity;
import android.util.Log;
import android.widget.RadioGroup;
import android.widget.RadioGroup.OnCheckedChangeListener;

@SuppressLint("NewApi")
public class MainActivity extends FragmentActivity {
	private HashMap<String, Fragment> fragMap;
	private FragmentManager fragManager;
	
	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_main);
		
		// 初始化
		initialize();
	}

	public void initialize() {
		fragManager = getFragmentManager();
		fragMap = new HashMap<String, Fragment>();
		
		RadioGroup radioGroup = (RadioGroup)findViewById(R.id.navigation_bar);
		// 设置RadioGroup选择的监听器
		radioGroup.setOnCheckedChangeListener(new OnCheckedChangeListener() {
			@Override
			public void onCheckedChanged(RadioGroup group, int checkedId) {
				Log.v("NAV", "radio_id=" + String.valueOf(checkedId));
				switchFragment(checkedId);
			};
		});
		
		// 缺省选择第一项
		radioGroup.check(R.id.nav_stock_pool);
	}
	
	// 切换Fragment
	protected void switchFragment(int selectId) {
		String key = String.valueOf(selectId);
		Fragment instance = (Fragment)fragMap.get(key);
		if (instance == null) {
			instance = createFragment(selectId);
			fragMap.put(key, instance);
		}
		
		FragmentTransaction transaction = fragManager.beginTransaction();
		transaction.replace(R.id.home_content, instance);
		transaction.addToBackStack(null);
		
		int day = ConfigHelper.getDay(1);
		getActionBar().setTitle(String.valueOf(day));
		
		transaction.commit();
	}
	
	
	protected Fragment createFragment(int selectId) {
		Fragment instance = null;
		
		switch (selectId) {
		case R.id.nav_stock_pool:
			instance = (Fragment)new PoolFragment();
			break;
			
		case R.id.nav_realtime:
			instance = (Fragment)new RealtimeFragment();
			break;
			
		case R.id.nav_stock_own:
			instance = (Fragment)new StockOwnFragment();
			break;
			
		case R.id.nav_user_center:
			instance = (Fragment)new UserCenterFragment();
			break;
		}
		
		return instance;
	}
}
