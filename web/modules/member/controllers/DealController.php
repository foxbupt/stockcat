<?php

/**
 * @desc 股票交易操作
 * @author fox
 * @date 2013/09/22
 *
 */
class DealController extends Controller 
{
	/**
	 * @desc 当前持有的股票列表
	 *
	 */
	public function actionOwn()
	{
		
	}
	
	/**
	 * @desc 买入操作
	 * @param $_POST['sid'] int
	 * @param $_POST['count'] int
	 * @param $_POST['price'] float 价格
	 * @param $_POST['bno'] int 批次编码, 可选
	 * @return json
	 */
	public function actionBuy()
	{
		
	}
	
	/**
	 * @desc 卖出操作
	 * @param $_POST['sid'] int
	 * @param $_POST['count'] int
	 * @param $_POST['price'] float 价格
	 * @param $_POST['bno'] int 批次编码, 可选
	 * @return json
	 */
	public function actionSell()
	{
		
	}
	
	/**
	 * @desc 查询某只股票的交易记录列表
	 * @param $_GET['bno'] 
	 * @param $_GET['sid']
	 * @param $_GET['type'] 交易记录类型, 可选
	 * @return json
	 */
	public function actionDataList()
	{
		
	}
	
	/**
	 * @desc 查询历史交易记录
	 * @param $_POST['begin_day']
	 * @param $_POST['end_day']
	 * @param $_POST['type']
	 * @param $_POST['page_no']
	 * @param $_POST['sort'] 指定排序字段, 可选
	 * @param $_POST['order'] 排序方式: asc 升序 desc 倒序
	 */
	public function actionHistory()
	{
		
	}
}
?>