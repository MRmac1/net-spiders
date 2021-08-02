# Define here the models for your scraped items
#
# See documentation in:
# https://docs.scrapy.org/en/latest/topics/items.html

import scrapy

class BiliItem(scrapy.Item):
    # vLevel1 - 科技，vLevel2 - 数码，vLevel3 - 手机
    vLevel1 = scrapy.Field()
    vLevel1Url = scrapy.Field()
    vLevel2 = scrapy.Field()
    vLevel2Url = scrapy.Field()
    vLevel3 = scrapy.Field()
    vLevel3Url = scrapy.Field()
