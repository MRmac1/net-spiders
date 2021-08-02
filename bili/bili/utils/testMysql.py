#! /usr/local/bin/python3
# -*- coding: UTF-8 -*-

from UsingMysql import UsingMysql

def check_it():

    with UsingMysql(log_time=True) as um:
        sql = "select count(id) as total from Channel"
        print("-- 当前数量: %d " % um.get_count(sql, None, 'total'))

if __name__ == '__main__':
    check_it()