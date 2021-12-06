# Define your item pipelines here
#
# Don't forget to add your pipeline to the ITEM_PIPELINES setting
# See: https://docs.scrapy.org/en/latest/topics/item-pipeline.html


# useful for handling different item types with a single interface
import pymysql
from pymysql import cursors

DB_HOST = '172.20.52.114'
DB_PORT = 3306
DB_NAME = 'bili'
DB_USER = 'root'
DB_PASS = 'sensegear'
class BiliPipeline:
    def __init__(self):
        db = pymysql.connect(host=DB_HOST, user=DB_USER, port=DB_PORT, password=DB_PASS)
        self.cursor = db.cursor()
    def process_item(self, item):
        print('BiliPipeline item', item)
        # cursor.execute('select version()')
        # data = cursor.fetchone()
        return item


# mysql://test:1qaz@WSX@localhost:3306/test?charset=utf8


