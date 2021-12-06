# This package will contain the spiders of your Scrapy project
#
# Please refer to the documentation for information on how to create and manage
# your spiders.
from urllib.parse import urlencode
from datetime import date, timedelta
import scrapy
import csv
from bili.items import BiliVideoItem
class BiliSpider(scrapy.Spider):
    name = "bili"
    allowed_domains = ["bilibili.com"]

    def __init__(self):
      self.sitePrefix = 'https://www.bilibili.com'
      self.siteSearchPrefix = 'https://s.search.bilibili.com/cate/search?'
      # B站目录，包含一级与二级目录
      self.grades = []

    def start_requests(self):
      with open('./config/Channel.csv') as csvfile:
          reader = csv.DictReader(csvfile)
          for row in reader:
            self.grades.append(row)
          gradesLevel2 = list(filter(lambda c: c.get('grade') == '2', self.grades))[0:1]
          for channel in gradesLevel2:
            grade2_url = self.sitePrefix + channel.get('route')
            print('开始爬取B站二级分类:', channel.get('name'), grade2_url)
            yield scrapy.Request(url=grade2_url, dont_filter=True, callback=self.parseTags, cb_kwargs=dict(grade2_url=grade2_url, channel=channel))

    # 解析二级目录下的三级标签
    def parseTags(self, response, channel, grade2_url):
      tag_list = response.xpath('//ul[@class="tag-list"]/li')
      if len(tag_list.getall()) > 0:
        # 删除首位的 “全部”
        tag_list.pop(0)
        tag_sample = tag_list[0:1]
        for tag_item in tag_sample:
          tag_url = grade2_url + tag_item.xpath('a/@href').extract_first()
          tag_name = tag_item.xpath('a/text()').extract_first()
          yield scrapy.Request(url= tag_url, dont_filter=True, callback=self.parseGradeLevel3, cb_kwargs=dict(tag_url=tag_url, channel=channel, tag_name=tag_name))
      else:
        print('tag_list is empty')

    # 抓取三级目录页
    def parseGradeLevel3(self, response, tag_url, channel, tag_name):
      # https://www.bilibili.com/v/douga/mad#/71744
      today = date.today()
      params = {
        'main_ver': 'v3',
        'search_type': 'video',
        'view_type': 'hot_rank',
        'order': 'click',
        'copy_right': '-1',
        'cate_id': channel.get('biliId'),
        'jsonp': 'jsonp',
        'keyword': tag_name,
        'page': 1,
        'pagesize': 2,
        'time_from': (today - timedelta(days=7)).strftime("%Y%m%d"),
        'time_to': today.strftime("%Y%m%d")
      }
      listUrl = self.siteSearchPrefix + urlencode(params)
      yield scrapy.Request(url= listUrl, dont_filter=True, callback=self.parseListPage, cb_kwargs=dict(params=params, channel=channel))

    def parseListPage(self, response, params, channel):
      result = response.json()

      for v in result.get('result', []):
        item = BiliVideoItem(v)
        yield item

      numPages = result.get('numPages')

      page = params.get('page')

      params['page'] = page + 1
      listUrl = self.siteSearchPrefix + urlencode(params)

      if (params['page'] < numPages and params['page'] <= 2):
        yield scrapy.Request(url= listUrl, dont_filter=True, callback=self.parseListPage, cb_kwargs=dict(params=params, channel=channel))


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
