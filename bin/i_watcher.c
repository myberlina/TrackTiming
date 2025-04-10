#include <stdlib.h>
#include <stdio.h>
#include <unistd.h>
#include <sys/inotify.h>
#include <errno.h>
#include <string.h>
#include <poll.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <strings.h>
#include <string.h>
#include <getopt.h>


#define USAGE "[-d] file\n"
#define OPTSTRING "d"

void my_abort(char *message) {
  fprintf(stderr, "Failed while %s, with %s\n", message, strerror(errno));
  exit(1);
}


int	notify_fd = -1;
int	watch_id = -1;
struct	inotify_event notify_evt;

int	debug = 0;


int main (int argc, char **argv) {
  int		opt;
  int		longindex;
  int32_t	bad_arg = 0;
  static struct option	long_options[] = {
    { "debug", no_argument, 0, 'd' },
    { 0,       0,           0, 0 }
  };
  while ((opt = getopt_long(argc, argv, "d", long_options, &longindex)) != -1) {
    switch (opt) {
      case 'd': {
	  debug=1;
	  break;
      }
      default:
	  bad_arg = 1;
    }
  }

  if (bad_arg) {
    fprintf(stderr, "Usage: %s %s\n", argv[0], USAGE);
    exit(EXIT_FAILURE);
  }

  if (optind == argc) {
    fprintf(stderr, "No file to watch provided\n");
    fprintf(stderr, "Usage: %s %s\n", argv[0], USAGE);
    exit(EXIT_FAILURE);
  }

  if (optind < (argc-1)) {
    fprintf(stderr, "Only one file to watch allowed\n");
    fprintf(stderr, "Usage: %s %s\n", argv[0], USAGE);
    exit(EXIT_FAILURE);
  }

  if (debug) fprintf(stderr, "Watching file %s\n", argv[optind]);

  int	slot;

  enum poll_fds { NOTIFY=0, LISTEN, CONN, MAX_FDs = 12 };
  struct pollfd	watch[MAX_FDs];

  memset(watch, 0, sizeof(watch));
  for (slot = CONN; slot<MAX_FDs; slot++)
    watch[slot].fd = -1;

  char	ignore[8192];


  notify_fd = inotify_init1(IN_CLOEXEC|IN_NONBLOCK);
  
  if (notify_fd < 0) my_abort("Trying inotify_init1");

  watch_id = inotify_add_watch(notify_fd, argv[optind], IN_MODIFY);

  if (watch_id < 0) my_abort("Trying inotify_add_watch");

  watch[NOTIFY].fd = notify_fd;
  watch[NOTIFY].events = POLLIN;

  watch[LISTEN].fd = 0; /* STDIN */
  watch[LISTEN].events = POLLIN;
  
  while (1) {
    int	free = CONN;
    int max;
    while ((free < MAX_FDs) && (watch[free].events == POLLIN))
      free++;

    max = free;
    for (slot = free; slot<MAX_FDs; slot++)
      if (watch[slot].fd > 0)
	max = slot+1;

    if (free < MAX_FDs)
      watch[LISTEN].events = POLLIN;
    else
      watch[LISTEN].events = 0;

    poll(watch, max, 1000);

    for (slot = CONN; slot<max; slot++) {
      if (watch[slot].revents & POLLIN) {
        if (debug) fprintf(stderr, "Data to read\n");
        int len = read(watch[slot].fd, ignore, sizeof(ignore));
        if (debug) fprintf(stderr, "Received and ignored %d bytes\n", len);
        if (len == 0) {
          close(watch[slot].fd);
          watch[slot].fd = -1;
          watch[slot].events = 0;
        }
      }
    }
    if (watch[NOTIFY].revents & POLLIN) {
      if (debug) fprintf(stderr, "Notify fired\n");
      read(notify_fd, ignore, sizeof(ignore));
      int  num_sent=0;
      for (slot = CONN; slot<max; slot++)
        if ((watch[slot].events == POLLIN) && (watch[slot].fd > 0)) {
          write(watch[slot].fd, "Change on file\n", 15);
	  num_sent++;
	}
      if (debug) fprintf(stderr, " Sent update x %d\n", num_sent);
    }
    if (watch[LISTEN].revents & POLLIN) {
      int conn = accept(0, NULL, NULL);
      if (conn > 0) {
        if (debug) fprintf(stderr, "Got new connection on listen port to slot %d\n", free);
        watch[free].fd = conn;
        watch[free].events = POLLIN;
      }
    }
  }
}

