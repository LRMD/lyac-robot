#!/usr/bin/python
import datetime

year = 2019

def round_name(week):
  return {
    1: 'VHF',
    2: 'UHF',
    3: 'SHF',
    4: 'Microwave',
  }[week]

for month in range(1,13):
  for day in range(1,29):
    loop_date = datetime.datetime(year,month,day)
    if loop_date.weekday() == 1:
      lyacround = (day - 1) / 7 + 1
      rounddate = loop_date.strftime('%Y-%m-%d') 
      print "INSERT INTO rounds VALUES('','"+ rounddate + "','" + round_name(lyacround) + "',"+repr(lyacround)+");"
      
