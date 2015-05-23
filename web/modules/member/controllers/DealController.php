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
				'actions'=>array('own', 'buy', 'sell', 'history'),
				'users'=>array('*'),
			),			
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
	
	/**
	 * @desc 当前持有的股票列表
	 * @param $_GET['state'] int 缺省为1
	 * @param $_GET['location'] int 可选
	 */
	public function actionOwn()
	{
		$day = CommonUtil::getParamDay(date('Ymd'));
		$uid = Yii::app()->user->isGuest? 0 : Yii::app()->user->getId();
		
		$location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
		$state = isset($_GET['state'])? intval($_GET['state']) : DealHelper::DEAL_STATE_HOLD;
		$userHoldList = DealHelper::getUserHoldList($uid, $state);
		
		$stockHqMap = array();
		$stockList = StockUtil::getStockList($location);
		foreach (array_keys($userHoldList) as $sid)
		{
			if (!in_array($sid, $stockList))
			{
				continue;
			}
			
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
		// $day = CommonUtil::getParamDay(date('Ymd'));
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
		$day = intval($_POST['day']);
		if (($count <= 0) || ($price <= 0))
		{
			$this->renderText(OutputUtil::json(array(), -1));
			return;
		}
		
		$stockInfo = StockUtil::getStockInfo($sid);
		$result = DealHelper::buyStock($uid, $sid, $day, $price, $count, $stockInfo['location']);
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
		// $day = CommonUtil::getParamDay(date('Ymd'));
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
		$day = intval($_POST['day']);
		if (($count <= 0) || ($price <= 0))
		{
			$this->renderText(OutputUtil::json(array(), -1));
			return;
		}
		
		$stockInfo = StockUtil::getStockInfo($sid);
		$result = DealHelper::sellStock($uid, $sid, $day, $price, $count, $stockInfo['location']);
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
	 * @desc 展现已结算的记录
	 * @param $_GET['location'] 缺省为1
	 */
	public function actionHistory()
	{
		$location = isset($_GET['location'])? intval($_GET['location']) : CommonUtil::LOCATION_CHINA;
		$uid = Yii::app()->user->isGuest? 0 : Yii::app()->user->getId();
		$historyList = DealHelper::getUserHoldList($uid, DealHelper::DEAL_STATE_CLOSE);
		
		$dealMap = $stockMap = array();
		$locationMap = StockUtil::getStockList($location);
		foreach ($historyList as $sid => $historyInfo)
		{
			if (!in_array($sid, $locationMap))
			{
				continue;
			}
			
			$stockMap[$sid] = StockUtil::getStockInfo($sid);
			$dealMap[$sid] = DealHelper::getDealList($uid, $sid, $historyInfo['batch_no']);
		}
		
		$this->render('history', array(
				'historyList' => $historyList,
				'stockMap' => $stockMap,
				'dealMap' => $dealMap,
			));
	}
}
?>