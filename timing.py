#!/usr/bin/python

import time
import pigpio
import numpy as np

import os
import signal
import sys

def handle_pdb(sig, frame):
    import pdb
    pdb.Pdb().set_trace(frame)

signal.signal(signal.SIGUSR1, handle_pdb)

import apsw
conn = apsw.Connection('Track_Time/Track_Time.db')
cur = conn.cursor()

cur.execute("select current_event  from current_event order by ROWID limit 1")
result=cur.fetchall()
if (len(result) == 1) :
    event = result[0][0]
    print("Timing for event", event)
else :
    event = -2
    print("No current event specified, storing with event", event)


def new_car():
    #cur.execute("select current_run  from current_run order by ROWID limit 1")
    #if (len(result) == 1) :
    #    new_car.run_num = result[0][0]
    cur.execute("SELECT current_event, current_run "
      "FROM current_event LEFT JOIN current_run");
    result=cur.fetchall()
    if (len(result) == 1) :
        event = result[0][0]
        new_car.run_num = result[0][1]
        print("run_num" , new_car.run_num)
    else :
        new_car.run_num = -2
        print("No current run set, using", new_car.run_num)
    cur.execute("BEGIN")
    cur.execute("select car_num  from next_car order by ord limit 1")
    result=cur.fetchall()
    if (len(result) == 1) :
        new_car.curr_car = result[0][0]
        print("curr_car" , new_car.curr_car)
        cur.execute("delete from next_car where car_num = ?", (new_car.curr_car, ))
        cur.execute("update current_car set current_car = ? where ROWID = 1", (new_car.curr_car, ))
        cur.execute("COMMIT")
    else :
        new_car.curr_car = new_car.next_missing_car
        new_car.next_missing_car = new_car.next_missing_car - 1
        print("No next car available, using" , new_car.curr_car)
        cur.execute("ROLLBACK")

new_car.run_num = 0
new_car.next_missing_car = -10
new_car.curr_car = -5

import time
epoch_time = int(time.time())
print("start ", epoch_time)

debounce = np.empty(60, dtype=np.uint32)

green_gpio=23
start_gpio=24
finish_gpio=25

def cb_green(gpio, level, tick):
    if pigpio.tickDiff(debounce[gpio],tick) < 200000 :
        debounce[gpio] = tick
        new_car()
        val = int(pigpio.tickDiff(start_tick, tick) / 1000)
        cur.execute("insert into green_time values ( ?, ?, ?, ?)", (event, new_car.run_num, new_car.curr_car, val))
        print(gpio, level, val)

def cb_start(gpio, level, tick):
    if pigpio.tickDiff(debounce[gpio],tick) < 300000 :
        debounce[gpio] = tick
        val = int(pigpio.tickDiff(start_tick, tick) / 1000)
        cur.execute("insert into start_time values ( ?, ?, ?, ?)", (event, new_car.run_num, new_car.curr_car, val))
        print(gpio, level, val)

def cb_finish(gpio, level, tick):
    if pigpio.tickDiff(debounce[gpio],tick) < 200000 :
        debounce[gpio] = tick
        val = int(pigpio.tickDiff(start_tick, tick) / 1000)
        cur.execute("insert into finish_time values ( ?, ?, ?, ?)", (event, new_car.run_num, new_car.curr_car, val))
        print(gpio, level, val)


pi = pigpio.pi()       # pi  accesses the local Pi's GPIO

start_tick = pi.get_current_tick()

normally_hi=0

if normally_hi:
    pi.set_pull_up_down(green_gpio, pigpio.PUD_UP)
    pi.set_pull_up_down(start_gpio, pigpio.PUD_UP)
    pi.set_pull_up_down(finish_gpio, pigpio.PUD_UP)
    
    cb1 = pi.callback(green_gpio, pigpio.FALLING_EDGE, cb_green)
    cb2 = pi.callback(start_gpio, pigpio.FALLING_EDGE, cb_start)
    cb3 = pi.callback(finish_gpio, pigpio.FALLING_EDGE, cb_finish)
else:
    pi.set_mode(green_gpio, pigpio.INPUT)
    pi.set_mode(start_gpio, pigpio.INPUT)
    pi.set_mode(finish_gpio, pigpio.INPUT)
    
    pi.set_pull_up_down(green_gpio, pigpio.PUD_DOWN)
    pi.set_pull_up_down(start_gpio, pigpio.PUD_DOWN)
    pi.set_pull_up_down(finish_gpio, pigpio.PUD_DOWN)
    
    cb1 = pi.callback(green_gpio, pigpio.RISING_EDGE, cb_green)
    #cb2 = pi.callback(start_gpio, pigpio.RISING_EDGE, cb_start)
    cb2 = pi.callback(start_gpio, pigpio.FALLING_EDGE, cb_start)
    cb3 = pi.callback(finish_gpio, pigpio.RISING_EDGE, cb_finish)

debounce[green_gpio] = start_tick
debounce[start_gpio] = start_tick + 2
debounce[finish_gpio] = start_tick + 4



while (1) :
    time.sleep(100)
    print(".")
    if (debounce[finish_gpio] > debounce[green_gpio]) :
        cur.execute("select current_event  from current_event order by ROWID limit 1")
        result=cur.fetchall()
        if (len(result) == 1) :
            new_event = result[0][0]
            if (new_event != event) :
                event = new_event
                print("Timing for event", event)
        else :
            print("Failed trying to check current event", event)


