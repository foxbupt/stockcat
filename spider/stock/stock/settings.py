# Scrapy settings for stock project
#
# For simplicity, this file contains only the most important settings by
# default. All the other settings are documented here:
#
#     http://doc.scrapy.org/topics/settings.html
#

BOT_NAME = 'stock'

SPIDER_MODULES = ['stock.spiders']
NEWSPIDER_MODULE = 'stock.spiders'

# Crawl responsibly by identifying yourself (and your website) on the user-agent
#USER_AGENT = 'stock (+http://www.yourdomain.com)'
