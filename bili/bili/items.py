# Define here the models for your scraped items
#
# See documentation in:
# https://docs.scrapy.org/en/latest/topics/items.html

import scrapy

class BiliVideoItem(scrapy.Item):
    # 上传时间
    senddate = scrapy.Field()
    # rank_offset - 基本等于 play
    rank_offset = scrapy.Field()
    # rank_score - ？
    rank_score = scrapy.Field()
    # 标签 - 逗号分隔，属于三级标签
    tag = scrapy.Field()
    # duration - 视频时长
    duration = scrapy.Field()
    # id - arc id，B 站有两套 id 和 视频地址
    id = scrapy.Field()
    # badgepay 未知
    badgepay = scrapy.Field()
    # pubdate 上架时间
    pubdate = scrapy.Field()
    # author up主
    author = scrapy.Field()
    # review 评论数
    review = scrapy.Field()
    # mid 中类？
    mid = scrapy.Field()
    # is_union_video 是否联合制作？
    is_union_video = scrapy.Field()
    # type 作品类型
    type = scrapy.Field()
    # arcrank ？
    arcrank = scrapy.Field()
    # pic 封面地址
    pic = scrapy.Field()
    # description 描述
    description = scrapy.Field()
    # is_pay 是否付费？
    is_pay = scrapy.Field()
    # favorites 点赞
    favorites = scrapy.Field()
    # arcurl 地址，用的是 id 字段拼接，应该是旧一套的id系统
    arcurl = scrapy.Field()
    # rank_index？
    rank_index = scrapy.Field()
    # play 播放数
    play = scrapy.Field()
    # video_review 弹幕
    video_review = scrapy.Field()
    # B站视频id，新
    bvid = scrapy.Field()
    # 作品标题
    title = scrapy.Field()
