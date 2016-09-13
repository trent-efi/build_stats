#!/usr/bin/python

import os
import sys
import time
from datetime import date
from datetime import datetime
from datetime import timedelta

today = date.today()
end_date = today
temp_date = date(today.year - 1, today.month, today.day )

while temp_date <= end_date:
    print temp_date
    tmp_yr = str(temp_date.year)
    tmp_mn = str(temp_date.month)
    tmp_dy = str(temp_date.day)

    if len(tmp_mn) == 1:
        tmp_mn = "0"+tmp_mn

    if len(tmp_dy) == 1:
        tmp_dy = "0"+tmp_dy
    
    cmd = "python /home/trentrus/public_html/stats/alpha_builder.py "+tmp_yr+"-"+tmp_mn+"-"+tmp_dy
    os.system(cmd)
    temp_date = temp_date  + timedelta(days=1)
