#!/usr/bin/python

import time
import pigpio
import numpy as np

import sqlite3
conn = sqlite3.connect('Track_Time.db')

curr_car = 0

def new_car(conn):
    cur = conn.cursor()
    cur.execute("select car_num  from next_car order by ord limit 1")

    new_car = cur.fetchall()
    print("new_car" , new_car)

    #for row in rows:
    #    print(row)



debounce = np.empty(60, dtype=np.uint32)

green_gpio=20
start_gpio=21
finish_gpio=22



def cbf(gpio, level, tick):
    #new_car(conn)
    if tick > debounce[gpio] :
        delta = tick - cbf.prev_tick
        print(gpio, level, tick, delta/1000000)
        cbf.prev_tick = tick
    debounce[gpio] = tick + 2000000

cbf.prev_tick = 0

pi = pigpio.pi()       # pi  accesses the local Pi's GPIO

pi.set_pull_up_down(green_gpio, pigpio.PUD_UP)
pi.set_pull_up_down(start_gpio, pigpio.PUD_UP)
pi.set_pull_up_down(finish_gpio, pigpio.PUD_UP)

cb1 = pi.callback(green_gpio, pigpio.FALLING_EDGE, cbf)
cb2 = pi.callback(start_gpio, pigpio.FALLING_EDGE, cbf)
cb3 = pi.callback(finish_gpio, pigpio.FALLING_EDGE, cbf)





while (1) :
    time.sleep(1000)
    print(" interrupt")

