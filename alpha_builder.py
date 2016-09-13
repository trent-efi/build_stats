#!/usr/bin/python

import os
import MySQLdb as mdb
import sys
import urllib2
import time
from datetime import date
from datetime import datetime
from datetime import timedelta
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

def parse_url(id):
    url = "http://calculus.efi.com/requests/"+str(id)

    cmd = "curl '"+url+"'"
    result = os.popen(cmd).read()
    return result
#end

def parse_region(region):
    id = -1

    if region == 'Fremont':
        id = 1
    elif region == 'vCommander Fremont':
        id = 2        
    elif region == 'IDC':
        id = 3
    elif region == 'vCommander IDC':
        id = 4
        
    return id
#end

def parse_codebase(codebase):
    id = -1
    if codebase == 'flame10':
        id = 1
    elif codebase == 'flame20':
        id = 2
    elif codebase == 'flame30':
        id = 3
    elif codebase == 'flame40':
        id = 4
    elif codebase == 'flame45':
        id = 5
    elif codebase == 'flame50':
        id = 6
    elif codebase == 'flame60':
        id = 7
    elif codebase == 'emerald10':
        id = 8
    elif codebase == 'barracuda10':
        id = 9
    elif codebase == 'ice21':
        id = 10
    elif codebase == 'ice22':
        id = 11
    elif codebase == 'ice23':
        id = 12
    elif codebase == 'tourmaline10':
        id = 13
    elif codebase == 'sky21':
        id = 14
    elif codebase == 'sky22':
        id = 15
    elif codebase == 'sky23':
        id = 16
    return str(id)
#end

def parse_os(os):
    id = 0
    if os == 'windows':
        id = 1
    elif os == 'linux':
        id = 2

    return str(id)
#end

"""
INSERT INTO requests (build_date, request_id, region_id, code_base_id, os_id, build_time) VALUES 
    (19700101, '000000', 1, 2, 14 ),
    (19700101, '000001', 3, 2, 23 ),
    (19700101, '000002', 2, 2, 31 ),
    (19700101, '000002', 2, 1, 3 );

CREATE TABLE requests(
    id            INTEGER      NOT NULL AUTO_INCREMENT,
    build_date    INTEGER      NOT NULL,
    request_id    VARCHAR(255) NOT NULL,
    region_id     INTEGER      NOT NULL,
    code_base_id  INTEGER      NOT NULL,
    os_id         INTEGER      NOT NULL,
    build_time    INTEGER      NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (region_id)    REFERENCES region(id),
    FOREIGN KEY (code_base_id) REFERENCES code_base(id),
    FOREIGN KEY (os_id)        REFERENCES os(id)
);


"""

def insert_values(date_str,request_id, region_id, code_base_id, os_id, build_time ):
    #mysql  --host=fcldb07 calculus --user=calculusread --password=dev45auth
    #con = mdb.connect('fcldb07', 'calculusread', 'dev45auth', 'calculus')

    #trentrus@trentrus-desktop ~ $ mysql --host=codesearch build_stats --user=efi_devops --password=efi_devops

    #mysql build_stats --user=root --password=codesearch --execute="select * from region"
    query =  "INSERT INTO requests (build_date, request_id, region_id, code_base_id, os_id, build_time) "
    query += "VALUES ("+date_str+", "+request_id+", "+region_id+", "+code_base_id+", "+os_id+", "+build_time+" );"

    cmd  = "mysql build_stats --user=root --password=codesearch --execute=\""+query+"\""
    os.system(cmd)
    ###########################################################################
    #con = mdb.connect('10.3.23.110', 'root', 'codesearch', 'build_stats')

    #with con:
    #    cur = con.cursor(mdb.cursors.DictCursor)
    #
    #    query =  "INSERT INTO requests (build_date, request_id, region_id, code_base_id, os_id, build_time) "
    #    query += "VALUES ("+date_str+", '"+request_id+"', "+region_id+", "+code_base_id+", "+os_id+", "+build_time+" );"
        
    #    cur.execute(query)
    #    con.close()
    #end with 
    ###########################################################################
#end

def Main():
    print "ALPHA BUILDER: "+sys.argv[1]

    foo = sys.argv[1]
    
    start = datetime(get_year(foo), get_month(foo), get_day(foo))
    start_yr = str(start.year)
    start_mn = str(start.month)
    start_dy = str(start.day)

    if len(start_mn) == 1:
        start_mn = "0"+start_mn

    if len(start_dy) == 1:
        start_dy = "0"+start_dy

    start_full = start_yr+"-"+start_mn+"-"+start_dy

    end = start + timedelta(days=1)

    end_yr = str(end.year)
    end_mn = str(end.month)
    end_dy = str(end.day)

    if len(end_mn) == 1:
        end_mn = "0"+end_mn

    if len(end_dy) == 1:
        end_dy = "0"+end_dy

    end_full = end_yr+"-"+end_mn+"-"+end_dy

    date_str = start_yr+start_mn+start_dy

    con = mdb.connect('fcldb07', 'calculusread', 'dev45auth', 'calculus')

    with con:
        cur = con.cursor(mdb.cursors.DictCursor)
        #query = "select id from requests where created_at >= '"+start_full+" 07:00:00' and created_at < '"+end_full+" 07:00:00' order by id;"

        #The time on the Calculus server is different from the database time; Need offset of 7 hours...
        query  = "SELECT rq.id, rg.name AS region "
        query += "FROM requests AS rq, regions AS rg "
        query += "WHERE rq.created_at >= '"+start_full+" 07:00:00' "
        query += "AND rq.created_at < '"+end_full+" 07:00:00' "
        query += "AND rq.region_id = rg.id "
        query += "ORDER BY rq.id;"

        cur.execute(query)

        rows = cur.fetchall()

        for row in rows:
            i = 0
            html = ''
            span = '</span>'
            id = row['id']
            region = row['region']

            region_id = parse_region(region)
            if  region_id < 0:
                continue

            region_id = str(region_id)

            html = parse_url(id)
            arr = html.split('\n')

            my_iter = iter(arr)
            for line in my_iter:
                build_id = str(id)+'xb'+str(i)
                reg_ex_status = 'status-'+build_id
                reg_ex_build = '<span id="'+build_id+'-arrow">'
                reg_ex_duration = '>duration:'

                #look for a passing build for our build_id
                if reg_ex_status in line and 'pass' in line:

                    while True:
                        line = my_iter.next()

                        #look for the codebase and os
                        if reg_ex_build in line:
                            location = line.find(span)+len(span)+1 
                            product = line[location:]

                            #Save variables
                            OS = ''
                            CodeBase = ''
                            hours = min = build_time = 0
                            arr = product.split()
                            if '_' in arr[0]:
                                foo = arr[0].split('_')
                                CodeBase = foo[0]
                                OS = foo[1]
                            elif 'ice' in arr[0]:
                                CodeBase = arr[0]
                                OS = 'windows'
                            elif 'sky' in arr[0]:
                                CodeBase = arr[0]
                                OS = 'linux'
                            else:
                                break 
                            #end

                            code_base_id = parse_codebase(CodeBase)
                            os_id = parse_os(OS)
                            date_str = start_yr+start_mn+start_dy
                            
                            while True:
                                line = my_iter.next()

                                if build_id in line and reg_ex_duration in line:
                                    line_arr = line.split(', ')
                                    time_str = line_arr[1]
                                    location = time_str.find(' ')
                                    time = time_str[:location]
                                    time_arr = time.split(':')
                                    hours = int(time_arr[0]) * 60
                                    min = int(time_arr[1])
                                    build_time = hours + min
                                    if build_time == 0:
                                        build_time = 1
                                    break 
                            #end while
                            
                            insert_values(str(date_str), str(id), str(region_id), str(code_base_id), str(os_id), str(build_time) )
                            break
                        #end if
                    #end while
                #end if
            #end for arr
        #end for rows 

        #con.close()# end slave db connection
 
#end

#--------------------------------------
# MAIN ENTRY POINT INTO SCRIPT
if __name__ == "__main__":
    Main()

"""
Database changed
mysql> select * from region;
+----+------------+
| id | name       |
+----+------------+
|  1 | Fremont    |
|  2 | VC_Fremont |
|  3 | IDC        |
|  4 | VC_IDC     |
+----+------------+
4 rows in set (0.00 sec)

mysql> select * from code_base;
+----+--------------+
| id | name         |
+----+--------------+
|  1 | flame10      |
|  2 | flame20      |
|  3 | flame30      |
|  4 | flame40      |
|  5 | flame45      |
|  6 | flame50      |
|  7 | flame60      |
|  8 | emerald10    |
|  9 | barracuda10  |
| 10 | ice21        |
| 11 | ice22        |
| 12 | ice23        |
| 13 | tourmaline10 |
| 14 | sky21        |
| 15 | sky22        |
| 16 | sky23        |
+----+--------------+
16 rows in set (0.00 sec)

mysql> select * from os;
+----+---------+
| id | name    |
+----+---------+
|  1 | windows |
|  2 | linux   |
+----+---------+
2 rows in set (0.00 sec)
"""
