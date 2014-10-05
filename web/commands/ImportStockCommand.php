<?php
/**
 * @desc 导入股票基本数据
 *
 */
Yii::import('application.components.*');
Yii::import('application.models.*');
Yii::import('application.modules.news.models.*');
Yii::import('application.modules.stock.models.*');

class ImportStockCommand extends CConsoleCommand
{
	public $tagList = array();
	
	public function run($args)
	{
		if (count($args) < 1)
		{
			echo "Usage: " . $args[0] . " <filename>\n";
			exit(1);
		}
		
		$lines = file($args[0]);
		
		foreach ($lines as $line)
		{
			$stockInfo = json_decode(trim($line), true);
			print_r($stockInfo);
			
			$result = $this->importStock($stockInfo);
			echo "op=import_stock code=" . $stockInfo['code'] . " name=" . $stockInfo['name'] . " result=" . ($result? 1 : 0) . "\n";
		}
		
		echo "finish\n";
	}
	
	public function importStock($stockInfo)
	{
		// 更新股票信息
		$loc = isset($stockInfo['loc'])? trim(trim($stockInfo['loc'], "省"), "市") : "";	

		$stockRecord = Stock::model()->findByAttributes(array('code' => $stockInfo['code']));
		if (empty($stockRecord))
		{
			echo "op=stock_record_nonexist code=" . $stockInfo['code'] . "\n";

            $stockRecord = new Stock();
            $stockRecord->type = 1;
            $stockRecord->code = $stockInfo['code'];
            $stockRecord->name = $stockInfo['name'];
            $stockRecord->ecode = self::formatEcode($stockInfo['ecode']);
            $stockRecord->alias = isset($stockInfo['alias'])? $stockInfo['alias'] : "";
            $stockRecord->location = isset($stockInfo['location'])? $stockInfo['location'] : 1;
            
            $stockRecord->company = isset($stockInfo['company'])? $stockInfo['company'] : "";
            $stockRecord->business = isset($stockInfo['business'])? $stockInfo['business'] : "";
            
            $stockRecord->profit = isset($stockInfo['profit'])? sprintf("%.2f", $stockInfo['profit']) : 0.0;
            $stockRecord->assets = isset($stockInfo['assets'])? sprintf("%.2f", $stockInfo['assets']) : 0.0;
            $stockRecord->dividend = isset($stockInfo['dividend'])? sprintf("%.2f", $stockInfo['dividend']) : 0.0;
            $stockRecord->capital = isset($stockInfo['captial'])? sprintf("%.2f", $stockInfo['captial']) : 0.0;
            $stockRecord->out_capital = isset($stockInfo['out_captial'])? sprintf("%.2f", $stockInfo['out_captial']) : sprintf("%.2f", $stockInfo['captial']);
            $stockRecord->create_time = time();

            $result = $stockRecord->save()? 1 : 0;
            if (!$result)
            {
                var_dump($stockRecord->getErrors());
            }
            $op = "add_stock";
		}
	    else
        {    
            // convert captial to capital
            $stockInfo['ecode'] = self::formatEcode($stockInfo['ecode']);
            if (isset($stockInfo['captial']))
            {
                $stockInfo['capital'] = $stockInfo['captial'];
            }
            if (isset($stockInfo['out_captial']))
            {
                $stockInfo['out_capital'] = $stockInfo['out_captial'];
            }

            foreach (array('dividend', 'profit', 'assets') as $key)
            {
                if ($stockInfo[$key] == 0.0)
                {
                    unset($stockInfo[$key]);
                }
                else
                {
                    $stockInfo[$key] = sprintf("%.2f", $stockInfo[$key]);
                }
            }
            unset($stockInfo['loc'], $stockInfo['code'], $stockInfo['captial'], $stockInfo['out_captial']);
            
            $result = $stockRecord->updateByPk($stockRecord->id, $stockInfo);
            $op = "update_stock";
        }
		echo "op=$op result=" . $result . " stock_id=" . $stockRecord->id . " code=" . $stockRecord['code'] . " name=" . $stockInfo['name'] . "\n";
		
		if (!empty($loc)) // 地域不为空则添加为tag
		{
    		$tid = 0;	
    		if (isset($this->tagList[$loc]))
    		{
    			$tid = $this->tagList[$loc];
    		}
    		else
    		{
    			Yii::import('common.components.Pinyinv2');
    			
    			$slug = Pinyinv2::getPinyin(mb_convert_encoding($loc, 'gbk', 'utf-8'));
    			$tid = CommonUtil::addTag($loc, $slug, CommonUtil::TAG_CATEGORY_LOCATION);
    			$this->tagList[$location] = $tid;
    			echo "op=add_tag location=" . $location . " slug=" .  $slug . " tid=" . $tid . "\n";
    		}
    		
    		StockUtil::addStockTag($stockRecord->id, $tid);		
	    }
		return $result;
	}

    // convert ecode to number
    public static function formatEcode($ecode)
    {
        if (is_numeric($ecode))
        {
            return $ecode;
        }

        $ecode = strtolower($ecode);
        if ("sh" == $ecode)
        {
            return CommonUtil::ECODE_SH;
        }
        else if ("sz" == $ecode)
        {
            return CommonUtil::ECODE_SZ;
        }
        else if ("hk" == $ecode)
        {
            return CommonUtil::ECODE_HK;
        }
        else if ("nasdaq" == $ecode)
        {
            return CommonUtil::ECODE_NASDAQ;
        }
        else if ("nyse" == $ecode)
        {
            return CommonUtil::ECODE_NYSE;
        }
    }
}
?>
