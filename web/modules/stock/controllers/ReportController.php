<?php

/**
 * @desc 股票研报列表和详情展示
 * @author fox
 * @date 2013/09/04
 *
 */
class ReportController extends Controller 
{
	/**
	 * @param $_GET['page_no'] 页码, 缺省为1
	 */
	public function actionList()
	{
		$pageNo = isset($_GET['page_no'])? intval($_GET['page_no']) : 1;
		$count = 10;
		
		// 总的记录条数, 用于计算总页数
		$totalCount = StockReport::model()->count(array('condition' => "status = 'Y'"));
		$totalPageCount = intval($totalCount / $count) + (($totalCount % $count == 0)? 0 : 1);
		
		// 按照时间倒序排列拉取
		$criteria = array(
						// 'select' => array('id', 'sid', 'name', 'title', 'day', 'agency', 'rank', 'goal_price'),
						'condition' => "status = 'Y'",
						'order' => 'day desc',
						'offset' => ($pageNo-1) * $count,
						'limit' => $count,
					);

		$recordList = StockReport::model()->findAll($criteria);
		$realCount = count($recordList);

		$this->render('list', array(
					'pageNo' => $pageNo,
					'totalCount' => $totalCount,
					'count' => $count,
					'realCount' => $realCount,
					'totalPageCount' => $totalPageCount,
					'recordList' => $recordList,
				));
	}
	
	/**
	 * @desc 查看研报详情
	 * @param $_GET['id']
	 */
	public function actionView()
	{
		if (!isset($_GET['id']))
		{
			throw new CHttpException(404);
		}
		
		$id = $_GET['id'];
		
		$record = StockReport::model()->findByPk($id, "status = 'Y'");
		if (empty($record))
		{
			throw new CHttpException(404);
		}
		
		$this->render('view', array(
					'record' => $record,
				));
	}
	
	public static function getDigest($content)
	{
		$parts = explode("\n", $content);
		
		$index = (mb_strlen($parts[0]) >= 30)? 0 : 1;
		return trim($parts[$index], "</p>");
	}
}
?>