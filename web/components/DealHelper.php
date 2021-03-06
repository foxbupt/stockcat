<?php

Yii::import('application.modules.member.models.*');
/**
 * @desc 交易操作封装
 * @author fox
 * @date 2015/03/08
 */
class DealHelper
{
	// 佣金费率万分之五, 印花税千分之一
	const COMMISION_FEE = 0.0005;
	const TAX_FEE = 0.001;
	
	// 股票状态: 0 所有 1 持仓  2 已结算(全部卖出)
	const DEAL_STATE_ALL = 0;
	const DEAL_STATE_HOLD = 1;
	const DEAL_STATE_CLOSE = 2;
	
	// 交易类型: 1 买入 2 卖出
	const DEAL_TYPE_BUY = 1;
	const DEAL_TYPE_SELL = 2;
	
	/**
	 * @desc 获取用户当前持仓的列表, 对于关闭状态, 会存在多个批次, 所以不能用sid作为key
	 *
	 * @param int $uid
	 * @param int $state
	 * @return array
	 */
	public static function getUserHoldList($uid, $state = self::DEAL_STATE_ALL)
	{
		$criteria = "uid = ${uid} and status = 'Y'";
		if ($state)
		{
			$criteria .= " and state = ${state}";
		}

		$holdList = array();
		$recordList = UserHold::model()->findAll(array(
					'condition' => $criteria,
					'order' => 'day desc',
				));
		foreach ($recordList as $record)
		{
			$holdList[] = $record->getAttributes();
		}
		
		return $holdList;
	}
	
	/**
	 * @desc 买入股票
	 *
	 * @param int $uid
	 * @param int $sid
	 * @param int $day
	 * @param double $price
	 * @param int $count
	 * @param int $location 缺省为LOCATION_CHINA
	 * @return
	 */
	public static function buyStock($uid, $sid, $day, $price, $count, $location = CommonUtil::LOCATION_CHINA)
	{
		$holdList = self::getUserHoldList($uid, self::DEAL_STATE_HOLD);
		$cost = $price * $count;
		$commission = (CommonUtil::LOCATION_CHINA == $location)? $cost * self::COMMISION_FEE : ceil($count/200*1);
		$tax = 0; //$cost * self::TAX_FEE;
		$amount = $cost + $commission + $tax;
		// var_dump($cost, $commission, $tax, $amount);

		$dealParams = array(
					'uid' => $uid, 
					'sid' => $sid,
					'day' => $day, 
					'deal_type' => self::DEAL_TYPE_BUY,
					'price' => $price,
					'count' => $count,
					'fee' => $cost,
					'commission' => CommonUtil::formatNumber($commission),
					'tax' => CommonUtil::formatNumber($tax),
					'amount' => CommonUtil::formatNumber($amount),
				);	
		$totalCount = $count;			
		if (isset($holdList[$sid]))
		{
			$holdInfo = $holdList[$sid];
			$dealParams['batch_no'] = $holdInfo['batch_no'];
			$totalCost = $holdInfo['cost'] + $amount;
			$totalCount = $holdInfo['count'] + $count;
			$avgPrice = CommonUtil::formatNumber($totalCost / $totalCount);
			$result = (1 == UserHold::model()->updateByPk($holdInfo['id'], array(
							'cost' => $totalCost,
							'total_count' => $totalCount,
							'count' => $totalCount,
							'price' => $avgPrice,	
							'update_time' => time(),
						)));
		}
		else
		{
			$dealParams['batch_no'] = $batchno = $day;
			$costPrice = CommonUtil::formatNumber($amount / $count);
			// var_dump($amount, $count, $costPrice);
			
			$record = new UserHold();
			$record->uid = $uid;
			$record->sid = $sid;
			$record->batch_no = $batchno;
			$record->day = $day;
			$record->count = $count;
			$record->total_count = $count;
			$record->state = self::DEAL_STATE_HOLD;
			$record->price = $costPrice;
			$record->cost = $amount;
			$record->create_time = $record->update_time = time();
			$record->status = 'Y';
			
			$result = $record->save()? true : false;			
		}
		
		StatLogUtil::log("buy_stock", array(
				'result' => $result? 1 : 0,
				'uid' => $uid,
				'sid' => $sid,
				'day' => $day,
				'count' => $count,
				'total_count' => $totalCount,
				'price' => $price,
				'cost' => $cost,
				'amount' => $amount,
			));
		
		if ($result)
		{
			UserDeal::addDealRecord(self::DEAL_TYPE_BUY, $dealParams);
		}
		
		return $result;
	}
	
	/**
	 * @desc 卖出股票
	 *
	 * @param int $uid
	 * @param int $sid
	 * @param int $day
	 * @param double $price
	 * @param int $count
	 * @param int $location
	 * @return bool
	 */
	public static function sellStock($uid, $sid, $day, $price, $count, $location = CommonUtil::LOCATION_CHINA)
	{
		$holdList = self::getUserHoldList($uid, self::DEAL_STATE_HOLD);
		$holdInfo = $holdList[$sid];
		if (empty($holdInfo) || ($holdInfo['count'] < $count))
		{
			return false;
		}
		
		$cost = $price * $count;
		if (CommonUtil::LOCATION_CHINA == $location)
		{
			$commision = CommonUtil::formatNumber($cost * self::COMMISION_FEE);
			$tax = CommonUtil::formatNumber($cost * self::TAX_FEE);
		} 
		else 
		{
			$commision = ceil($count/200 * 1.00);
			$tax = 0;
		}
		$amount = $cost - $commision - $tax;

		$dealParams = array(
					'uid' => $uid, 
					'sid' => $sid,
					'day' => $day,
					'batch_no' => $holdInfo['batch_no'],	 
					'deal_type' => self::DEAL_TYPE_SELL,
					'price' => $price,
					'count' => $count,
					'fee' => $cost,
					'commission' => $commision,
					'tax' => $tax, 
					'amount' => $amount,
				);				

		// 部分卖出时成本价格保持不变, 把获利部分计入即可
		$fields = array();
		$fields['count'] = $restCount = $holdInfo['count'] - $count;	
		$fields['amount'] = $amount + $holdInfo['amount'];
		$fields['update_time'] = time();
		
		// 全部卖出时, 把状态置为已结算
		if (0 == $restCount)
		{
			$fields['close_day'] = $day;
			$fields['state'] = self::DEAL_STATE_CLOSE;
			$fields['profit'] = $totalProfit = ($amount + $holdInfo['amount']) - $holdInfo['cost'];
		} 
		else 
		{
			$fields['state'] = self::DEAL_STATE_HOLD;
			$fields['profit'] = $totalProfit = $amount - $count / $holdInfo['count'] * $holdInfo['cost'] + $holdInfo['profit'];
		}
		
		$fields['profit_portion'] = CommonUtil::formatNumber($totalProfit / $holdInfo['cost'] * 100);
		$result = (1 == UserHold::model()->updateByPk($holdInfo['id'], $fields));
		StatLogUtil::log("sell_stock", array_merge(array('result' => $result? 1 : 0), $fields));	
		
		if ($result)
		{
			UserDeal::addDealRecord(self::DEAL_TYPE_SELL, $dealParams);
		}
		
		return $result;	
	}
	
	/**
	 * @desc 获取股票的交易记录列表
	 *
	 * @param int $uid
	 * @param int $sid
	 * @param int $batchNo
	 * @return array
	 */
	public static function getDealList($uid, $sid, $batchNo)
	{
		$dealList = array();
		
		$recordList = UserDeal::model()->findAll(array(
					'condition' => "uid = ${uid} and batch_no = ${batchNo} and sid = ${sid}",
					'order' => 'day asc',					
		));
		foreach ($recordList as $record)
		{
			$dealList[] = $record->getAttributes();
		}
		
		return $dealList;
	}
}
?>