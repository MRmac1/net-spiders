import pymysql
from pymysql import cursors

DB_HOST = '172.20.52.114'
DB_PORT = 3306
DB_NAME = 'bili'
DB_USER = 'root'
DB_PASS = 'sensegear'

db = pymysql.connect(host=DB_HOST, user=DB_USER, port=DB_PORT, password=DB_PASS)
cursor = db.cursor()
cursor.execute('select version()')
data = cursor.fetchone()

print('Database version:', data)

db.close()


# data = {
#     'id': '20120001',
#     'name': 'Bob',
#     'age': 20
# }
# table = 'students'
# keys = ', '.join(data.keys())
# values = ', '.join(['%s'] * len(data))
# sql = 'INSERT INTO {table}({keys}) VALUES ({values}) ON DUPLICATE KEY UPDATE'.format(table=table, keys=keys, values=values)
# update = ','.join([" {key} = %s".format(key=key) for key in data])
# sql += update
# try:
#    if cursor.execute(sql, tuple(data.values())*2):
#        print('Successful')
#        db.commit()
# except:
#     print('Failed')
#     db.rollback()
# db.close()