import pymysql
from dbutils.pooled_db import PooledDB, SharedDBConnection
from urllib.parse import urlparse
class MysqlPool(object):

    def __init__(self, url):
        self.url = url
        if not url:
            return
        url = urlparse(url)
        self.POOL = PooledDB(
            creator=pymysql, 
            maxconnections=10, # 连接池的最大连接数 
            maxcached=10, 
            maxshared=10,
            blocking=True,  
            setsession=[],  
            host=url.hostname,
            port=url.port or 3306,
            user=url.username,
            password=url.password,
            database=url.path.strip().strip('/'),
            charset='utf8',
        )
    def __new__(cls, *args, **kw):
        if not hasattr(cls, '_instance'):
            cls._instance = object.__new__(cls)
        return cls._instance

    def connect(self):
        conn = self.POOL.connection()
        cursor = conn.cursor(cursor=pymysql.cursors.DictCursor)
        return conn, cursor

    def connect_close(self,conn, cursor):
        cursor.close()
        conn.close()

    def fetch_all(self,sql, args):
        conn, cursor = self.connect()
        if args is None:
            cursor.execute(sql)
        else:
            cursor.execute(sql, args)
        record_list = cursor.fetchall()
        return record_list

    def fetch_one(self,sql, args):
        conn, cursor = self.connect()
        cursor.execute(sql, args)
        result = cursor.fetchone()
        return result

    def insert(self,sql, args):
        conn, cursor = self.connect()
        row = cursor.execute(sql, args)
        conn.commit()
        self.connect_close(conn, cursor)
        return row