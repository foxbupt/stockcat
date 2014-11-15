<head>
	<script src="http://echarts.baidu.com/build/dist/echarts.js"></script>
</head>

<body>
    <!-- 为ECharts准备一个具备大小（宽高）的Dom -->
    <div id="main" style="height:600px"></div>
    
    <script type="text/javascript">
    	var trendList =  eval ("(" + '<?php echo json_encode($trendList); ?>' + ")");
    	
        // 路径配置
        require.config({
            paths:{ 
                echarts: 'http://echarts.baidu.com/build/dist'
            }
        });

     	// 使用
        require(
            [
                'echarts',
                'echarts/chart/line' // 按需加载
            ],
            function(ec) {
                // 基于准备好的dom，初始化echarts图表
                var myChart = ec.init(document.getElementById('main')); 
                
                var option = {
                    tooltip: {
                        show: true,
                        trigger: 'axis',
                        formatter: function(params,ticket,callback) {
                    		var day = params[0][1];
                            var res = '日期 : ' + day;
                            for (var i = 0, l = params.length; i < l; i++) {
                                res += '<br/>' + params[i][0] + ' : ' + params[i][2];
                            }

                            if (day in trendList) {
                                var trendInfo = trendList[day];
								res += '<br/>开始日期 : ' + trendInfo['start_day'];
								res += '<br/>趋势 : ' + trendInfo['trend_text']; 
                            }
                            
                            return res;
                        }
                    },
                    toolbox: {
                        show : true,
                        feature : {
                            mark : {show: true},
                            // dataZoom : {show : true}, 
                            dataView : {show: true, readOnly: false},
                            magicType : {show: true, type: ['line']},
                            restore : {show: true},
                            saveAsImage : {show: true}
                        }
                    },
                    title: {
						text: '<?php echo $stockInfo["name"] . "(" . $stockInfo["code"] . ") [" . $startDay . "~" . $endDay . "]"; ?>',
                    },
                    legend: {
                        data: ['价格']
                    },
                    xAxis : [
                        {
                            // name: '日期',
                            type: 'category',
                            scale: true,
                            data: <?php echo json_encode($days); ?>
                        }
                    ],
                    yAxis : [
                        {
                            // name: '价格',
                            type: 'value',
                            precision: 1,
                         	min: <?php echo $minValue; ?>,
                            max: <?php echo $maxValue; ?>
                        }
                    ],
                    series : [
                        {
                            name: "价格",
                            type: "line",
                            data: <?php echo json_encode($values); ?>,
                            markPoint : {
                                data : [
                                    {type : 'max', name: '最高价'},
                                    {type : 'min', name: '最低价'}
                                ]
                            },
                        }
                    ]
                };
        
                // 为echarts对象加载数据 
                myChart.setOption(option); 
            }
        );
    </script>
</body>

