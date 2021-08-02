#! /usr/local/bin/python3
# -*- coding: UTF-8 -*-
import traceback
import pymysql as mdb

DB_HOST = '172.16.2.118'
DB_PORT = 3306
DB_NAME = 'bili'
DB_USER = 'root'
DB_PASS = '123123'

SPIDER_INTERVAL = 10  # 至少保证10秒以上，否则容易被封

ERR_NO = 0  # 正常
ERR_REFUSE = 1  # 爬虫爬取速度过快，被拒绝
ERR_EX = 2  # 未知错误

class Db(object):
    def __init__(self):
        self.dbconn = None
        self.dbcurr = None

    def check_conn(self):
        try:
            self.dbconn.ping()
        except:
            return False
        else:
            return True

    def conn(self):
        self.dbconn = mdb.connect(host=DB_HOST, port=DB_PORT, db=DB_NAME, user=DB_USER, password=DB_PASS)
        print('self.dbconn', self.dbconn)
        self.dbconn.autocommit(False)
        self.dbcurr = self.dbconn.cursor()

    def fetchone(self):
        return self.dbcurr.fetchone()

    def fetchall(self):
        return self.dbcurr.fetchall()

    def execute(self, sql, args=None, falg=False):
        if not self.dbconn:
            # 第一次链接数据库
            self.conn()
        try:
            if args:
                rs = self.dbcurr.execute(sql, args)
            else:
                rs = self.dbcurr.execute(sql)
            return rs
        except Exception as e:
            if self.check_conn():
                print('execute error', e)
                traceback.print_exc()
            else:
                print('reconnect mysql')
                self.conn()
                if args:
                    rs = self.dbcurr.execute(sql, args)
                else:
                    rs = self.dbcurr.execute(sql)
                return rs

    def commit(self):
        self.dbconn.commit()

    def rollback(self):
        self.dbconn.rollback()

    def close(self):
        self.dbconn.close()
        self.dbcurr.close()

    def last_row_id(self):
        return self.dbcurr.lastrowid