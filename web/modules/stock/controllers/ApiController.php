<?php

/**
 * @desc 数据接口请求
 * @author: fox
 * @date: 2014/04/16
 */
class ApiController extends CController
{
	/**
	 * @desc 获取股票池列表
	 * @param $_GET['day'] 选择日期, 可选
	 * @param $_GET['cont_day'] 连续天数
	 * // @param $_GET['wave'] 所处波段可选, 取值: 1 下跌  3 上升
	 * // @param $_GET['order'] 排序字段可选, 取值有: price_amount/price_portion/volume_portion, 缺省取值为price_portion
	 * @return json
	 */
	public function actionPoollist()
	{
		$this->layout = false;
		
		if (!isset($_GET['cont_day']))
		{
			echo OutputUtil::json(array(), -1, "invalid params");
			return;
		}
		
		$data = array();
		
		$contDay = intval($_GET['cont_day']);
		if (isset($_GET['day']))
		{
			$day = intval($_GET['day']);
		}
		else 
		{
			$currentDay = date('Ymd');
			$day = (date('H') <= 19)? CommonUtil::getPastOpenDay($currentDay, 1) : $currentDay;
		}

        $lastDay = CommonUtil::getPastOpenDay($day, 1);
        $data = DataModel::getContList($lastDay, $day, $contDay);
		
		echo OutputUtil::json($data);
	}
	
	/**
	 * @desc 获取股票价格突破列表
	 * @param $_GET['day'] 
	 * @param $_GET['type'] 高低点类型, 取值为high/low/all, all时忽略threshold的值
	 * @param $_GET['threshold'] 突破类型, 可选, 取值: 0-4, 缺省为0 表示获取全部, 1 表示<=threshold的类型
	 * @return json
	 */
	public function actionPrice()
	{
		$this->layout = false;
		
		if (!isset($_GET['day'], $_GET['type']))
		{
			echo OutputUtil::json(array(), -1, "invalid params");
			return;
		}
		
		$day = intval($_GET['day']);
        $lastDay = CommonUtil::getPastOpenDay($day, 1);
		$type = isset($_GET['type'])? $_GET['type'] : "all";
		$threshold = isset($_GET['threshold'])? intval($_GET['threshold']) : 0;
		
        $highTypes = $lowTypes = array();
        if ("high" == $type || "all" == $type)
        {
            $highTypes = (0 == $threshold)? range(1, 4) : range(1, $threshold);
        }
        if ("low" == $type || "all" == $type)
        {
            $lowTypes = (0 == $threshold)? range(1, 4) : range(1, $threshold);
        }

		$data = DataModel::getThresholdList($lastDay, $day, $highTypes, $lowTypes);
		echo OutputUtil::json($data);
	}

	/**
	 * @desc 获取实时上涨列表, 只返回>=rf的股票
	 * @param $_GET['day'] 选择日期, 可选
	 * @param $_GET['rf'] 上涨因子, 可选, 缺省为5
	 * @param $_GET['ratio'] 量比, 可选, 缺省为2
	 * @return json
	 */
	public function actionRealtime()
	{
		$this->layout = false;
        
        $rf = isset($_GET['rf'])? intval($_GET['rf']) : 5;
        $ratio = isset($_GET['ratio'])? floatval($_GET['ratio']) : 2;
        $day = isset($_GET['day'])? $_GET['day'] : date('Ymd');
        
        $day = CommonUtil::getParamDay($day);
        $data = array();

        $datamap = DataModel::getRealtimeList($day);        
        foreach ($datamap as $sid => $dataItem)
        {
            if (($dataItem['rf'] < $rf) || ($dataItem['policy']['volume_ratio'] < $ratio))
            {
                continue;
            }
            
            $data[] = $dataItem;
        }

        echo OutputUtil::json($data);
    }

	/**
	 * @desc 获取快速拉升列表
	 * @param $_GET['day'] 选择日期, 可选
	 * @return json
	 */
    public function actionRapidRise()
    {
		$this->layout = false;

        $day = isset($_GET['day'])? intval($_GET['day']) : date('Ymd');
        $day = CommonUtil::getParamDay($day);
        $data = DataModel::getRapidList($day, True);

        echo OutputUtil::json($data);
    }

    /**
     * @desc 获取涨幅前30的列表
     * @param $_GET['day']
     * @return json
     */
    public function actionUpLimit()
    {
        $this->layout = false;

        $day = isset($_GET['day'])? intval($_GET['day']) : date('Ymd');
        $day = CommonUtil::getParamDay($day);

        $data = DataModel::getUpLimitList($day);
        echo OutputUtil::json($data);
    }

    /**
     * @desc 对拉升数据排序
     *
     * @param array $rapidInfo1
     * @param array $rapidInfo2
     * @return int
     */
    public function cmpRapidFunc($rapidInfo1, $rapidInfo2)
    {
		if ($rapidInfo1["now_time"] == $rapidInfo2["now_time"])
		{
			return ($rapidInfo1["vary_portion"] < $rapidInfo2["vary_portion"])? 1 : -1;
		}
		
        // 按照时间的大小逆序排列
		return ($rapidInfo1["now_time"] < $rapidInfo2["now_time"])? 1 : -1;
    }
}
?>
