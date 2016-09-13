#!/usr/bin/python

import os
import sys
import time
from datetime import date
from datetime import datetime
from datetime import timedelta

def get_year(date_str):
    return int(date_str[:4])
#end

def get_month(date_str):
    return int(date_str[5:7])
#end

def get_day(date_str):
    return int(date_str[8:10])
#end

foo = sys.argv[1]

#----------------------------------
#Start date
start = datetime(get_year(foo), get_month(foo), get_day(foo))
temp_date = start 
end_date = start + timedelta(days=365)

while temp_date <= end_date:
    print temp_date
    tmp_yr = str(temp_date.year)
    tmp_mn = str(temp_date.month)
    tmp_dy = str(temp_date.day)

    if len(tmp_mn) == 1:
        tmp_mn = "0"+tmp_mn

    if len(tmp_dy) == 1:
        tmp_dy = "0"+tmp_dy
    
    cmd = "python /home/trentrus/public_html/stats/weekly_build_controller.py "+tmp_yr+"-"+tmp_mn+"-"+tmp_dy
    os.system(cmd)
    temp_date = temp_date  + timedelta(days=7)
