# This package will contain the spiders of your Scrapy project
#
# Please refer to the documentation for information on how to create and manage
# your spiders.
import scrapy

from bili.items import BiliItem

class BiliSpider(scrapy.Spider):
    name = "bili"
    allowed_domains = ["bilibili.com"]
    start_urls = [
        "https://www.bilibili.com",
    ]

    def parse(self, response):
      vLevel1List = response.selector.xpath('//div[@id="primaryChannelMenu"]/span')
      vLevel1List.pop()
      for sel in vLevel1List:
        item = BiliItem()
        item['vLevel1'] = sel.xpath('div/a/span/text()').extract()
        item['vLevel1Url'] = sel.xpath('div/a/@href').extract()
        yield item

    # # 爬取顶层 level 的主页
    # def parseBiliTopLevel(self, response):
    #     pass
    # response.selector.xpath('//div[@id="subnav"]/ul/li')[1].xpath('a/@href').extract()
    # response.selector.xpath('//div[@id="subnav"]/ul/li')[1].xpath('a/text()').extract()
    # return { level2: '综合', url: 'https://www.bilibili.com/v/douga/other/'}
    #
    # 访问 https://www.bilibili.com/v/douga/other/ 获取三级标签
    # response.selector.xpath('//div[@class="tag-list-cnt"]/ul/li')
    # xpath('@title').extract() xpath('a/@href').extract()
    # 获取 { level3: '名侦探柯南', url: '#/8881'}
    
    # 爬取三级分类下的视频资源
    # 


    # def parse(self, response, **kwargs):
    #     response.selector.xpath('//div[@id="videolist_box"]/div[@class="vd-list-cnt"]/ul')
    # 抓取三级分类需要采用直接请求 jsonp 的方式
    #   curl 'https://api.bilibili.com/x/tag/ranking/archives?tag_id=3253&rid=24&type=0&pn=1&ps=20&jsonp=jsonp&callback=jsonCallback_bili_93334705091281148' \
    # -H 'authority: api.bilibili.com' \
    # -H 'pragma: no-cache' \
    # -H 'cache-control: no-cache' \
    # -H 'sec-ch-ua: " Not;A Brand";v="99", "Google Chrome";v="91", "Chromium";v="91"' \
    # -H 'sec-ch-ua-mobile: ?0' \
    # -H 'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36' \
    # -H 'accept: */*' \
    # -H 'sec-fetch-site: same-site' \
    # -H 'sec-fetch-mode: no-cors' \
    # -H 'sec-fetch-dest: script' \
    # -H 'accept-language: zh-CN,zh;q=0.9'
    # --compressed
