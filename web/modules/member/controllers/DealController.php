<?php

/**
 * @desc 股票交易操作分析
 * 		TODO: 借鉴IB的收益分析, 做的简单一点, 重点是关注买入日期、分析当前走势提醒到时间后抛出
 * @author fox
 * @date 2013/09/22
 *
 */
class DealController extends Controller 
{
/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('own', 'buy', 'sell'),
				'users'=>array('*'),
			),			
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	
	/**
	 * @desc 当前持有的股票列表
	 *
	 */
	public function actionOwn()
	{
		$day = CommonUtil::getParamDay(date('Ymd'));
		$uid = Yii::app()->user->isGuest? 0 : Yii::app()->user->getId();
		$userHoldList = DealHelper::getUserHoldList($uid, DealHelper::DEAL_STATE_HOLD);
		
		$stockHqMap = array();
		foreach (array_keys($userHoldList) as $sid)
		{
			$stockHqMap[$sid] = DataModel::getHQData($sid, $day);
		}
		
		$this->render('own', array(
				'day' => $day,
				'userHoldList' => $userHoldList,
				'stockHqMap' => $stockHqMap,
				'trendMap' => CommonUtil::getConfigObject("stock.direction"),
        		'opMap' => CommonUtil::getConfigObject("stock.op"),
			));
	}
	
	/**
	 * @desc 买入操作
	 * @param $_POST['sid']/$_POST['code'] int
	 * @param $_POST['count'] int
	 * @param $_POST['price'] float 价格
	 * @return json
	 */
	public function actionBuy()
	{
		$this->layout = false;
		$day = CommonUtil::getParamDay(date('Ymd'));
		$uid = Yii::app()->user->isGuest? 0 : Yii::app()->user->getId();
		
		if (isset($_POST['sid']))
		{
			$sid = intval($_POST['sid']);
		}
		else if (isset($_POST['code']))
		{
			$stockMap = StockUtil::getStockMap();
			$sid = $stockMap[$_POST['code']];	
		} 
		 
		$count = intval($_POST['count']);
		$price = floatval($_POST['price']);
		if (($count <= 0) || ($price <= 0))
		{
			$this->renderText(OutputUtil::json(array(), -1));
			return;
		}
		
		$result = DealHelper::buyStock($uid, $sid, $day, $price, $count);
		$this->renderText(OutputUtil::json(array(), $result? 0 : -2));
	}
	
	/**
	 * @desc 卖出操作
	 * @param $_POST['sid']/$_POST['code'] int
	 * @param $_POST['count'] int
	 * @param $_POST['price'] float 价格
	 * @return json
	 */
	public function actionSell()
	{
		$this->layout = false;
		$day = CommonUtil::getParamDay(date('Ymd'));
		$uid = Yii::app()->user->isGuest? 0 : Yii::app()->user->getId();
		
		if (isset($_POST['sid']))
		{
			$sid = intval($_POST['sid']);
		}
		else if (isset($_POST['code']))
		{
			$stockMap = StockUtil::getStockMap();
			$sid = $stockMap[$_POST['code']];	
		} 
		 
		$count = intval($_POST['count']);
		$price = floatval($_POST['price']);
		if (($count <= 0) || ($price <= 0))
		{
			$this->renderText(OutputUtil::json(array(), -1));
			return;
		}
		
		$result = DealHelper::sellStock($uid, $sid, $day, $price, $count);
		$this->renderText(OutputUtil::json(array(), $result? 0 : -2));
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