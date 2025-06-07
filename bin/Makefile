
BIN_SCRIPTS = timingd results_conf results_to_static forward_results get_times

SBIN_SCRIPTS = home_tunnel web_redir web_redir_mgmt CreateTimingDatabase

CreateDatabase = Create_Database.sql

all:	i_watcher

CFLAGS =  -std=gnu99 -Wall -Werror -g

clean:
	rm -f i_watcher

install: i_watcher
	mkdir -p $(DESTDIR)/usr/bin
	install --mode 511 i_watcher $(DESTDIR)/usr/bin
	install --mode 555 $(BIN_SCRIPTS) $(DESTDIR)/usr/bin
	mkdir -p $(DESTDIR)/usr/sbin
	install --mode 555 $(SBIN_SCRIPTS) $(DESTDIR)/usr/sbin
	mkdir -p $(DESTDIR)/usr/share/tracktiming/
	install --mode 444 $(CreateDatabase) $(DESTDIR)/usr/share/tracktiming/



