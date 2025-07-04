
all:
	$(MAKE) -C bin $@

install:
	echo $(MAKE) -C bin install
	$(MAKE) -C bin install
	echo find html to $(DESTDIR)
	mkdir -p $(DESTDIR)/usr/share/tracktiming
	find html | fgrep -v -e .gitignore -e .swp | cpio -pvdum $(DESTDIR)/var/www
	#find html | fgrep -v -e .gitignore -e .swp | cpio -pvdum $(DESTDIR)/usr/share/tracktiming/
	mkdir -p $(DESTDIR)/var/www/html/icons
	cp icons/StopWatch-*.png icons/Timing.webmanifest $(DESTDIR)/var/www/html/icons/
	echo find etc to $(DESTDIR)/
	find etc | fgrep -v -e .gitignore -e .swp | cpio -pvdum $(DESTDIR)
	echo find systemd to $(DESTDIR)/usr/lib
	mkdir -p $(DESTDIR)/lib
	find systemd | fgrep -v -e .gitignore -e .swp | cpio -pvdum $(DESTDIR)/lib
	install --mode 2775 --owner www-data --group www-data -d $(DESTDIR)/data
	install --mode 2770 --owner www-data --group www-data -d $(DESTDIR)/data/Track_Time
