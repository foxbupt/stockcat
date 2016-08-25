#!/usr/bin/python
#-*- coding: UTF-8 -*-
#author: fox
#desc: 操作机会收益回归分析
#date: 2016/07/02

import sys, re, json, os
import datetime, time, logging, logging.config
sys.path.append('../../../../server')
import smtplib
from email.mime.text import MIMEText
from email.header import Header
from pyutil.util import Util, safestr, format_log    
import redis, pandas as pd 

mail_user = {'user': 'supervisedboy@163.com', 'password': 'gd@1018'}
def notify_mail(sender, receivers, subject, content, mail_config):
	message = MIMEText(content, 'plain', 'utf-8')
	from_text = mail_config['from'] if 'from' in mail_config else "笨财猫"
	message['From'] = Header(from_text, 'utf-8')
	#message['To'] =  Header("测试", 'utf-8')
	message['Subject'] = Header(subject, 'utf-8')

	try:
		smtpObj = smtpObj = smtplib.SMTP()
		smtpObj.connect('mail.163.com', 25)    # 25 为 SMTP 端口号
		smtpObj.login(mail_user['user'], mail_user['password'])  
		smtpObj.sendmail(sender, receivers, message.as_string())
	except smtplib.SMTPException as e:
		print "err=send_mail " + e
		return False
		
	return True
			
def core(config_info, location, day):
	offset = 0
	key = "chance-" + str(day)
	cur_timenumber = get_timenumber(location)

	redis_config = config_info['REDIS']
    conn = redis.StrictRedis(redis_config['host'], redis_config['port'])   
	
	while cur_timenumber >= 93000 and cur_timenumber <= 120000:
		count = conn.llen(key)
		if count <= offset:
			continue
		
		chance_list = conn.lrange(key, offset, count)
		content_list = []
		for chance_item in chance_list:
			content_list.append(format_log("chance_info", chance_item))
		
		content = "\r\n".join(content_list)
		send_result = notify_mail("supervisedboy@163.com", ["huli@vip.qq.com"], content, {})
		print "op=notify_mail time= " + str(cur_timenumber) + " result=" + str(send_result) 
		time.sleep(60)
		
	print "finish"
	
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print "Usage: " + sys.argv[0] + " <conf> [location] [day]"
        sys.exit(1)

    config_info = Util.load_config(sys.argv[1])
    print config_info
    config_info['DB']['port'] = int(config_info['DB']['port'])
    config_info['REDIS']['port'] = int(config_info['REDIS']['port'])

    # 初始化日志
    print config_info['LOG'], config_info['LOG']['conf']
    logging.config.fileConfig(config_info['LOG']['conf'])
                                                            
    location = 1                                                        
    day = get_current_day(location) 
    if len(sys.argv) >= 3:
        location = int(sys.argv[2])
    if len(sys.argv) >= 4:
        day = int(sys.argv[3])    
        
    core(config_info, "chance-queue", location, day)