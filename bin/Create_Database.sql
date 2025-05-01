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


#CREATE TABLE IF NOT EXISTS entrant_info( event INT NOT NULL, car_num INT NOT NULL, car_name NOT NULL, car_info,
# CONSTRAINT Tuple UNIQUE (event,car_num));

#CREATE TABLE IF NOT EXISTS entrant_info( event INT NOT NULL, car_num INT NOT NULL, car_name NOT NULL, car_info, special,
# CONSTRAINT Tuple UNIQUE (event,car_num));

CREATE TABLE IF NOT EXISTS entrant_info( event INT NOT NULL, car_num INT NOT NULL, car_name NOT NULL, car_info, special, class, car_car, car_entrant, run_order,
 CONSTRAINT Tuple UNIQUE (event,car_num));

# ALTER TABLE entrant_info ADD class;
# ALTER TABLE entrant_info ADD car_car;
# ALTER TABLE entrant_info ADD car_entrant;
# ALTER TABLE entrant_info ADD run_order;

CREATE TABLE IF NOT EXISTS class_info( class NOT NULL unique, class_info, record );

CREATE TABLE IF NOT EXISTS event_info( num INT NOT NULL unique, name );

CREATE TABLE IF NOT EXISTS next_car( car_num INT NOT NULL unique, ord INT NOT NULL unique);

CREATE TABLE IF NOT EXISTS green_time ( event INT, run INT, car_num INT, time_ms INT );

CREATE TABLE IF NOT EXISTS start_time ( event INT, run INT, car_num INT, time_ms INT );

CREATE TABLE IF NOT EXISTS finish_time ( event INT, run INT, car_num INT, time_ms INT );


DROP VIEW IF EXISTS results;
CREATE VIEW results (event , run , car_num , rt_ms , et_ms , ft_ms , red )
as select green_time.event, green_time.run, green_time.car_num,
       (start_time.time_ms - green_time.time_ms),
       (finish_time.time_ms - start_time.time_ms),
       (finish_time.time_ms - min(green_time.time_ms, start_time.time_ms)),
       (start_time.time_ms < green_time.time_ms)
from green_time
 left join finish_time on green_time.event = finish_time.event
                     and green_time.run = finish_time.run
                     and green_time.car_num = finish_time.car_num
                     and green_time.time_ms < finish_time.time_ms
 left join start_time on green_time.event = start_time.event
                     and green_time.run = start_time.run
                     and green_time.car_num = start_time.car_num
                     and green_time.time_ms-2000 < start_time.time_ms
where start_time.time_ms < finish_time.time_ms
/* results(event,run,car_num,rt_ms,et_ms,ft_ms,red) */;


DROP VIEW IF EXISTS rt_order;
CREATE VIEW rt_order (event , run , car_num, best_rt, red)
as select event, run, car_num, rt_ms, red from results group by event, car_num order by red, min(red*10000000+rt_ms)
/* rt_order(event,run,car_num,best_rt,red) */;

DROP VIEW IF EXISTS et_order;
CREATE VIEW et_order (event , run , car_num, best_et, red)
as select event, run, car_num, et_ms, red from results group by event, car_num order by red, min(red*10000000+et_ms)
/* et_order(event,run,car_num,best_et,red) */;

DROP VIEW IF EXISTS ft_order;
CREATE VIEW ft_order (event , run , car_num, best_ft, red)
as select event, run, car_num, ft_ms, red from results group by event, car_num order by red, min(red*10000000+ft_ms)
/* ft_order(event,run,car_num,best_ft,red) */;


DROP VIEW IF EXISTS hc_results;
CREATE VIEW hc_results (event , run , car_num , rt_ms , et_ms , ft_ms , red )
as select green_time.event, green_time.run, green_time.car_num,
       (start_time.time_ms - green_time.time_ms),
       (finish_time.time_ms - start_time.time_ms),
       (finish_time.time_ms - green_time.time_ms),
       (start_time.time_ms < green_time.time_ms)
from green_time
 left join finish_time on green_time.event = finish_time.event
                     and green_time.run = finish_time.run
                     and green_time.car_num = finish_time.car_num
                     and green_time.time_ms < finish_time.time_ms
 left join start_time on green_time.event = start_time.event
                     and green_time.run = start_time.run
                     and green_time.car_num = start_time.car_num
                     and green_time.time_ms-2000 < start_time.time_ms
where green_time.time_ms < finish_time.time_ms
/* hc_results(event,run,car_num,rt_ms,et_ms,ft_ms,red) */;

DROP VIEW IF EXISTS hc_order;
CREATE VIEW hc_order (event , run , car_num, best_ft, red)
as select event, run, car_num, ft_ms, red from hc_results group by event, car_num order by red, min(red*10000000+ft_ms)
/* hc_order(event,run,car_num,best_ft,red) */;


DROP VIEW IF EXISTS set_order;
CREATE VIEW set_order (event , car_num , run_order, new_order) AS
 select entrant_info.event, entrant_info.car_num, entrant_info.run_order,
        printf('%03d', ROW_NUMBER() over (order by green_time.ROWID NULLS LAST,  next_car.ord) ) as new_order
  from entrant_info, current_event, current_run
  LEFT JOIN  green_time  ON entrant_info.event   = green_time.event
			AND entrant_info.car_num = green_time.car_num
    			AND green_time.run = current_run.current_run
  LEFT JOIN  next_car  ON entrant_info.car_num = next_car.car_num
  where entrant_info.event = current_event.current_event
/* set_order(event,car_num,run_order,new_order) */;
DROP TRIGGER IF EXISTS re_order;
CREATE TRIGGER re_order
    INSTEAD OF  UPDATE OF run_order
    ON set_order
BEGIN
    update entrant_info
    set run_order = new.run_order
    where event = old.event
      and car_num = old.car_num;
END;

/* select * from set_order;	*/;
/* update set_order set run_order = new_order;	*/;

COMMIT;

