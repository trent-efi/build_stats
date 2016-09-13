#!/usr/bin/python

import os
import sys
import time
from datetime import date
from datetime import datetime
from datetime import timedelta
import MySQLdb as mdb
import urllib2
import pdb

def get_year(date_str):
    return int(date_str[:4])
#end

def get_month(date_str):
    return int(date_str[5:7])
#end

def get_day(date_str):
    return int(date_str[8:10])
#end

def insert_values(start_full, end_full, region_id, code_base_id, os_id, total, mean):

    con = mdb.connect('codesearch', 'efi_devops', 'efi_devops', 'build_stats')
    with con:
        cur = con.cursor(mdb.cursors.DictCursor)       
        query  = "INSERT INTO weekly_stats(start_date, end_date, region_id, code_base_id, os_id, total, mean) "
        query += "VALUES ("+start_full+", "+end_full+", "+str(region_id)+", "+str(code_base_id)+", "+str(os_id)+", "+str(total)+", "+str(mean)+")"
        cur.execute(query)

    con.close()
#end

def Main():
    print "Weekly Build Controller: "+sys.argv[1]

    foo = sys.argv[1]

    #----------------------------------
    #Start date
    start = datetime(get_year(foo), get_month(foo), get_day(foo))
    start_yr = str(start.year)
    start_mn = str(start.month)
    start_dy = str(start.day)

    if len(start_mn) == 1:
        start_mn = "0"+start_mn

    if len(start_dy) == 1:
        start_dy = "0"+start_dy

    start_full = start_yr+start_mn+start_dy

    #----------------------------------
    #End date: plus 7 days 
    end = start + timedelta(days=7)
    end_yr = str(end.year)
    end_mn = str(end.month)
    end_dy = str(end.day)

    if len(end_mn) == 1:
        end_mn = "0"+end_mn

    if len(end_dy) == 1:
        end_dy = "0"+end_dy

    end_full = end_yr+end_mn+end_dy

    con = mdb.connect('codesearch', 'efi_devops', 'efi_devops', 'build_stats')

    start_date = start_full
    total = 0
    region_id = 0
    code_base_id = 0
    os_id = 0
    mean = 0

    with con:
        cur = con.cursor(mdb.cursors.DictCursor)

        query  = "SELECT region_id, code_base_id, os_id, COUNT(id) AS total, AVG(build_time) AS mean "
        query += "FROM requests "
        query += "WHERE build_date >= "+start_full+" "
        query += "AND build_date < "+end_full+" "
        query += "GROUP BY region_id, code_base_id, os_id"

        cur.execute(query)

        rows = cur.fetchall()
        for row in rows:
             total = row['total']
             region_id = row['region_id']
             code_base_id = row['code_base_id']
             os_id = row['os_id']
             mean = row['mean']
             insert_values(start_full, end_full, region_id, code_base_id, os_id, total, mean)
             
    con.close() 
    print "Completed running weekly_build_controller.py"

#end Main

#--------------------------------------
# MAIN ENTRY POINT INTO SCRIPT
if __name__ == "__main__":
    Main()

