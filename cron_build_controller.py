#!/usr/bin/python

import os
import sys
import time
from datetime import date
from datetime import datetime
from datetime import timedelta

today = date.today()
temp_date = today + timedelta(days=-1)
tmp_yr = str(temp_date.year)
tmp_mn = str(temp_date.month)
tmp_dy = str(temp_date.day)

if len(tmp_mn) == 1:
    tmp_mn = "0"+tmp_mn

if len(tmp_dy) == 1:
    tmp_dy = "0"+tmp_dy

cmd = "python /home/trentrus/public_html/stats/alpha_builder.py "+tmp_yr+"-"+tmp_mn+"-"+tmp_dy
os.system(cmd)
