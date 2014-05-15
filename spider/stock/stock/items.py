# Define here the models for your scraped items
#
# See documentation in:
# http://doc.scrapy.org/topics/items.html

from scrapy.item import Item, Field

class StockItem(Item):
    name = Field()
    code = Field()
    company = Field()
    location = Field()
    business = Field()
    captial = Field()
    out_captial = Field()
    profit = Field()
    assets = Field()

class StockTagItem(Item):
    name = Field()
    slug = Field()
    category = Field()
    stock_code = Field() 

class StockDataItem(Item):
    sid = Field()
    code = Field()
    day = Field()
    last_close_price = Field()
    open_price = Field()
    high_price = Field()
    low_price = Field()
    close_price = Field()
    volume = Field()
    amount = Field()
    vary_price = Field()
    vary_portion = Field()
    exchange_portion = Field()
    swing = Field()

class StockDetailItem(Item):
    sid = Field()
    code = Field()
    day = Field()
    time = Field()
    price = Field()
    avg_price = Field()
    volume = Field()
    swing = Field()


class StockReportItem(Item):
    name = Field()
    title = Field()
    content = Field()
    day = Field()
    rank = Field()
    goal_price = Field()
    agency = Field()

class StockEventItem(Item):
    code = Field()
    event_date = Field()
    title = Field()
    content = Field()

class StockFundItem(Item):
    id = Field()
    code = Field()
    day = Field()
    total = Field()
    small = Field()
    medium = Field()
    large = Field()
    super = Field()
    amount = Field()
    vary_portion = Field()

class StockEarningItem(Item):
    code = Field()
    name = Field()
    type = Field()
    event_date = Field()
    report_date = Field()
    past_profit = Field()
    min_portion = Field()
    max_portion = Field()
    digest = Field()
    content = Field()
