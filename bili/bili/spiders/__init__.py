# This package will contain the spiders of your Scrapy project
#
# Please refer to the documentation for information on how to create and manage
# your spiders.
import scrapy
from bili.items import BiliItem
from bili.db import Db
class BiliSpider(scrapy.Spider):
    name = "bili"
    allowed_domains = ["bilibili.com"]

    def __init__(self):
      self.db = Db()
      self.sitePrefix = 'https://www.bilibili.com'

    def start_requests(self):
      grade2_urls = self.db.execute('select * from Channel where grade = 2 limit 1')
      if grade2_urls < 0:
        print('grade2_urls empty')
      fetch_all = self.db.fetchall()
      for channel in fetch_all:
        # (22, 'MMD·3D', 25, 2, '/v/douga/mmd', 1, '使用MMD（MikuMikuDance）和其他3D建模类软件制作的视频')
        grade2_url = self.sitePrefix + channel[4]
        yield scrapy.Request(url=grade2_url, callback=self.parseTags, cb_kwargs=dict(grade2_url=grade2_url, channel=channel))

    def parseTags(self, response, channel, grade2_url):
      tag_list = response.xpath('//ul[@class="tag-list"]/li')
      if len(tag_list.getall()) > 0:
        # 删除首位的 “全部”
        tag_list.pop(0)
        for tag_item in tag_list:
          tag_url = grade2_url + tag_item.xpath('a/@href').extract_first()
          tag_name = tag_item.xpath('a/text()').extract_first()
          print('tag_url', tag_url)
          print('tag_name', tag_name)
          yield scrapy.Request(url= tag_url, callback=self.parse, cb_kwargs=dict(tag_url=tag_url, channel=channel))
      else:
        print('tag_list is empty')

    def parse(self, response, tag_url, channel):
      print('tag_url', tag_url)
      print('channel', channel)
      pass

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
