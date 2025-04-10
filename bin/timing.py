#!/usr/bin/python

import time
import pigpio
import numpy as np

import os
import signal
import sys

button_lockout=22 * 1000 * 1000 * 1000  # 22 seconds in nanoseconds

def reset_debounce(sig, frame):
    debounce[button_gpio] = start_tick
    debounce[green_gpio] = start_tick
    debounce[start_gpio] = start_tick + 2
    debounce[finish_gpio] = start_tick + 4

def debug(sig, frame):
    import pdb
    pdb.Pdb().set_trace(frame)
    debug.cb_debug=1
    print("Turning on cb_debug")

debug.cb_debug=0

signal.signal(signal.SIGUSR2, debug)

def fake_timing(sig, frame):
    now = pi.get_current_tick();
    if (fake_timing.evt == 0):
        fake_timing.evt = 1
        cb_green(green_gpio, 2, now)
    elif (fake_timing.evt == 1):
        fake_timing.evt = 2
        cb_start(start_gpio, 2, now)
    elif (fake_timing.evt == 2):
        fake_timing.evt = -1
        cb_finish(finish_gpio, 2, now)
    elif (fake_timing.evt == -1):
        fake_timing.evt = 0
        cb_button(button_gpio, 2, now)

def fake_timing_0(sig, frame):
    now = pi.get_current_tick();
    cb_button(button_gpio, 2, now)

def fake_timing_1(sig, frame):
    now = pi.get_current_tick();
    cb_green(green_gpio, 2, now)

def fake_timing_2(sig, frame):
    now = pi.get_current_tick();
    cb_start(start_gpio, 2, now)

def fake_timing_3(sig, frame):
    now = pi.get_current_tick();
    cb_finish(finish_gpio, 2, now)

fake_timing.evt=-1

signal.signal(signal.SIGUSR1, fake_timing)

signal.signal(signal.SIGRTMIN, fake_timing_0)
signal.signal(signal.SIGRTMIN+1, fake_timing_1)
signal.signal(signal.SIGRTMIN+2, fake_timing_2)
signal.signal(signal.SIGRTMIN+3, fake_timing_3)

import apsw
conn = apsw.Connection('/data/Track_Time/Track_Time.db', apsw.SQLITE_OPEN_READWRITE)
cur = conn.cursor()
cur.execute('pragma busy_timeout=5000')
cur.execute('pragma wal_checkpoint(full);')

Green_f = open("/data/state_Green", "w")
Start_f = open("/data/state_Start", "w")
Finish_f = open("/data/state_Finish", "w")

def new_car():
    #cur.execute("select current_run  from current_run order by ROWID limit 1")
    #if (len(result) == 1) :
    #    new_car.run_num = result[0][0]
    cur.execute("SELECT current_event, current_run "
      "FROM current_event LEFT JOIN current_run");
    result=cur.fetchall()
    if (len(result) == 1) :
        new_car.event = result[0][0]
        new_car.run_num = result[0][1]
        print("run_num" , new_car.run_num)
    else :
        new_car.run_num = -2
        print("No current run set, using", new_car.run_num)
    cur.execute("BEGIN")
    cur.execute("select car_num  from next_car order by ord limit 2")
    result=cur.fetchall()
    if (len(result) >= 1) :
        new_car.curr_car = result[0][0]
        print("curr_car" , new_car.curr_car)
        if (len(result) > 1) :
          new_car.next_car = result[1][0]
        else :
          new_car.next_car = 666
        cur.execute("delete from next_car where car_num = ?", (new_car.curr_car, ))
        cur.execute("update current_car set current_car = ? where ROWID = 1", (new_car.curr_car, ))
        cur.execute("COMMIT")
    else :
        new_car.curr_car = new_car.next_missing_car
        new_car.next_missing_car = new_car.next_missing_car - 1
        print("No next car available, using" , new_car.curr_car)
        cur.execute("ROLLBACK")
        new_car.next_car = new_car.curr_car

new_car.event=-1
new_car.run_num = 0
new_car.next_missing_car = 1001
new_car.curr_car = 1000
new_car.next_car = 666
new_car.tick = 0
new_car.tick_val = 0
new_car.time=time.monotonic_ns() - button_lockout

#  0 - No Car (prev car finished) ,   1 - Car has green light  ,   2 - Car has a start time
new_car.state = 0  #  No Car

import time
debounce = np.empty(60, dtype=np.uint32)

green_gpio=23
start_gpio=24
finish_gpio=25
button_gpio=17

def cb_button(gpio, level, tick):
    debo=debounce[gpio]
    debounce[gpio] = tick
    if pigpio.tickDiff(debo,tick) > 300000 :  # 0.3s debounce.
        mon_now=time.monotonic_ns()
        val = int(pigpio.tickDiff(start_tick, tick) / 1000)
        if ( (mon_now - new_car.time) > button_lockout ) :
            new_car.time = mon_now
            new_car()
            new_car.tick = pi.get_current_tick()
            new_car.tick_val = val
        if debug.cb_debug:
            print(gpio, level, val, pigpio.tickDiff(tick, new_car.tick), new_car.tick, new_car.time)
        new_car.state = 1  #  Car given buton
    else:
        if debug.cb_debug:
            print(gpio, pigpio.tickDiff(debo,tick), "debounce")
    if debug.cb_debug:
        print("debug ", gpio, tick, debo)

def cb_green(gpio, level, tick):
    debo=debounce[gpio]
    debounce[gpio] = tick
    if pigpio.tickDiff(debo,tick) > 300000 :  # 0.3s debounce.
        mon_now=time.monotonic_ns()
        val = int(pigpio.tickDiff(start_tick, tick) / 1000)
        if ( (mon_now - new_car.time) > button_lockout ) :
            new_car.time = mon_now
            new_car()
            new_car.tick = pi.get_current_tick()
            new_car.tick_val = val
            new_car.state = 1  #  Car given green
        else:
            if (val < new_car.tick_val) :
                val=val+4294967     # undo the wrap for database entry
            if (new_car.state != 2) :  #  red lighted
                new_car.state = 1  #  Car given green
        cur.execute("insert into green_time values ( ?, ?, ?, ?)", (new_car.event, new_car.run_num, new_car.curr_car, val))
        if debug.cb_debug:
            print(gpio, level, val, pigpio.tickDiff(tick, new_car.tick), new_car.tick, new_car.time)
        Green_f.seek(0)
        Green_f.write(str(new_car.curr_car)+"    ")
        Green_f.flush()
    else:
        if debug.cb_debug:
            print(gpio, pigpio.tickDiff(debo,tick), "debounce")
    if debug.cb_debug:
        print("debug ", gpio, tick, debo)

def cb_start(gpio, level, tick):
    debo=debounce[gpio]
    debounce[gpio] = tick
    #if pigpio.tickDiff(debo,tick) > 1200000 :  # 1.2s debounce, and ignore rear wheels.
    if pigpio.tickDiff(debo,tick) > 300000 :  # 0.3s debounce.
        val = int(pigpio.tickDiff(start_tick, tick) / 1000)
        if (val < new_car.tick_val) :
            val=val+4294967     # undo the wrap for database entry
        if (new_car.state == 1) :  # Got green, and this is first start trigger
          cur.execute("insert into start_time values ( ?, ?, ?, ?)", (new_car.event, new_car.run_num, new_car.curr_car, val))
          new_car.state = 2  # Have a start trigger
        elif (new_car.state == 0) :  # No car with green - red light
          # Red light
          cur.execute("insert into start_time values ( ?, ?, ?, ?)", (new_car.event, new_car.run_num, -new_car.next_car, val))
          if (new_car.next_car == 666) :  # Last car of this run - first car of next run ??
            cur.execute("insert into start_time values ( ?, ?, ?, ?)", (new_car.event, new_car.run_num + 1, -new_car.next_car, val))
        else :  #  new_car.state == 2   #  Already have a start line trigger
          # Rear wheels slow, next car staging??
          cur.execute("insert into start_time values ( ?, ?, ?, ?)", (new_car.event, new_car.run_num, -new_car.curr_car, val))
        if debug.cb_debug:
            print(gpio, level, val)
        Start_f.seek(0)
        Start_f.write(str(new_car.curr_car)+"    ")
        Start_f.flush()
    else:
        if debug.cb_debug:
            print(gpio, pigpio.tickDiff(debo,tick), "debounce")
    if debug.cb_debug:
        print("debug ", gpio, level, tick, debo)

def cb_finish(gpio, level, tick):
    debo=debounce[gpio]
    debounce[gpio] = tick
    if pigpio.tickDiff(debo,tick) > 300000 :  # 0.3s debounce.
        val = int(pigpio.tickDiff(start_tick, tick) / 1000)
        if (val < new_car.tick_val) :
            val=val+4294967     # undo the wrap for database entry
        cur.execute("insert into finish_time values ( ?, ?, ?, ?)", (new_car.event, new_car.run_num, new_car.curr_car, val))
        if debug.cb_debug:
            print(gpio, level, val)
        Finish_f.seek(0)
        Finish_f.write(str(new_car.curr_car)+"    ")
        Finish_f.flush()
        new_car.state = 0  #  Car finished
        new_car.time = time.monotonic_ns() - button_lockout    #  Clear the new_car  lockout
    else:
        if debug.cb_debug:
            print(gpio, pigpio.tickDiff(debo,tick), "debounce")
    if debug.cb_debug:
        print("debug ", gpio, tick, debo)


cur.execute("SELECT current_event, current_run "
      "FROM current_event LEFT JOIN current_run");
result=cur.fetchall()
if (len(result) == 1) :
    new_car.event = result[0][0]
    new_car.run_num = result[0][1]
    print("Timing for event", new_car.event, "  Run", new_car.run_num)
else :
    new_car.event = -2
    print("No current event specified, storing with event", new_car.event)

pi = pigpio.pi()       # pi  accesses the local Pi's GPIO

start_tick = pi.get_current_tick()

new_car.tick = start_tick

epoch_time = int(time.time())
print("start ", epoch_time, "  tick ", start_tick)

debounce[green_gpio] = start_tick
debounce[start_gpio] = start_tick + 2
debounce[finish_gpio] = start_tick + 4

pi.set_mode(button_gpio, pigpio.INPUT)
pi.set_mode(green_gpio, pigpio.INPUT)
pi.set_mode(start_gpio, pigpio.INPUT)
pi.set_mode(finish_gpio, pigpio.INPUT)

pi.set_pull_up_down(button_gpio, pigpio.PUD_DOWN)
pi.set_pull_up_down(green_gpio, pigpio.PUD_DOWN)
pi.set_pull_up_down(start_gpio, pigpio.PUD_DOWN)
pi.set_pull_up_down(finish_gpio, pigpio.PUD_DOWN)
    
normally_hi=0
hsv_fest=0

if normally_hi:
    cb1 = pi.callback(green_gpio, pigpio.FALLING_EDGE, cb_green)
    cb2 = pi.callback(start_gpio, pigpio.FALLING_EDGE, cb_start)
    cb3 = pi.callback(finish_gpio, pigpio.FALLING_EDGE, cb_finish)
elif hsv_fest:
    cb0 = pi.callback(button_gpio, pigpio.RISING_EDGE, cb_button)
    cb1 = pi.callback(green_gpio, pigpio.RISING_EDGE, cb_green)
    #cb2 = pi.callback(start_gpio, pigpio.RISING_EDGE, cb_start)
    cb2 = pi.callback(start_gpio, pigpio.FALLING_EDGE, cb_start)  # Stage with beam broken
    cb3 = pi.callback(finish_gpio, pigpio.RISING_EDGE, cb_finish)
else:
    cb0 = pi.callback(button_gpio, pigpio.RISING_EDGE, cb_button) # Not necessarily used
    cb1 = pi.callback(green_gpio, pigpio.RISING_EDGE, cb_green)
    cb2 = pi.callback(start_gpio, pigpio.RISING_EDGE, cb_start)
    cb3 = pi.callback(finish_gpio, pigpio.RISING_EDGE, cb_finish)


prev_tick = pi.get_current_tick()

while (1) :
    time.sleep(400000)
    print(".", end="", flush=1)
    if (debounce[finish_gpio] > debounce[green_gpio]) :
        cur.execute("select current_event  from current_event order by ROWID limit 1")
        result=cur.fetchall()
        if (len(result) == 1) :
            new_event = result[0][0]
            if (new_event != new_car.event) :
                new_car.event = new_event
                print("Timing for event", new_car.event)
        else :
            print("Failed trying to check current event", new_car.event)
    #now_tick = pi.get_current_tick()
    #if (now_tick < prev_tick) :
    #    print("Tick has wrapped. Tick:", now_tick, "  Green:", debounce[green_gpio], "  Start:", debounce[start_gpio], "  Finish:", debounce[finish_gpio])
    #prev_tick = now_tick


