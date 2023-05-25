/*
 sqlite> .tables
    current_car    entrant_info   finish_time    next_car       start_time   
    current_event  et_order       ft_order       results      
    current_run    event_info     green_time     rt_order     
*/

PRAGMA busy_timeout=5000;
PRAGMA journal_mode;
/* PRAGMA journal_mode=delete; */
PRAGMA journal_mode=wal;

CREATE TABLE IF NOT EXISTS current_car( current_car INT NOT NULL);
INSERT INTO current_car VALUES ( 0 );

CREATE TABLE IF NOT EXISTS current_event( current_event INT);
INSERT INTO current_event VALUES ( 0 );


CREATE TABLE IF NOT EXISTS current_run( current_run INT);
INSERT INTO current_run VALUES ( 0 );


CREATE TABLE IF NOT EXISTS entrant_info( event INT NOT NULL, car_num INT NOT NULL, car_name NOT NULL, car_info,
 CONSTRAINT Tuple UNIQUE (event,car_num));

CREATE TABLE IF NOT EXISTS event_info( num INT NOT NULL unique, name );

CREATE TABLE IF NOT EXISTS next_car( car_num INT NOT NULL unique, ord INT NOT NULL unique);

CREATE TABLE IF NOT EXISTS green_time ( event INT, run INT, car_num INT, time_ms INT );

CREATE TABLE IF NOT EXISTS start_time ( event INT, run INT, car_num INT, time_ms INT );

CREATE TABLE IF NOT EXISTS finish_time ( event INT, run INT, car_num INT, time_ms INT );


DROP VIEW results;
CREATE VIEW results (event , run , car_num , rt_ms , et_ms , ft_ms )
as select green_time.event, green_time.run, green_time.car_num,
       (start_time.time_ms - green_time.time_ms),
       (finish_time.time_ms - start_time.time_ms),
       (finish_time.time_ms - green_time.time_ms)
from green_time
 left join finish_time on green_time.event = finish_time.event
                     and green_time.run = finish_time.run
                     and green_time.car_num = finish_time.car_num
                     and green_time.time_ms < finish_time.time_ms
 left join start_time on green_time.event = start_time.event
                     and green_time.run = start_time.run
                     and green_time.car_num = start_time.car_num
                     and green_time.time_ms < start_time.time_ms
where start_time.time_ms < finish_time.time_ms
/* results(event,run,car_num,rt_ms,et_ms,ft_ms) */;

CREATE VIEW rt_order (event , run , car_num, best_rt)
as select event, run, car_num, min(rt_ms) from results group by event, car_num order by min(rt_ms)
/* rt_order(event,run,car_num,best_rt) */;

CREATE VIEW et_order (event , run , car_num, best_et)
as select event, run, car_num, min(et_ms) from results group by event, car_num order by min(et_ms)
/* et_order(event,run,car_num,best_et) */;

CREATE VIEW ft_order (event , run , car_num, best_ft)
as select event, run, car_num, min(ft_ms) from results group by event, car_num order by min(ft_ms)
/* ft_order(event,run,car_num,best_ft) */;

