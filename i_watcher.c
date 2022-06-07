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

int	conn = -1;

int	debug=1;


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

  enum poll_fds { NOTIFY=0, LISTEN, CONN, MAX_FDs };
  struct pollfd	watch[MAX_FDs];

  char	ignore[8192];


  notify_fd = inotify_init1(IN_CLOEXEC|IN_NONBLOCK);
  
  if (notify_fd < 0) my_abort("Trying inotify_init1");

  watch_id = inotify_add_watch(notify_fd, argv[optind], IN_MODIFY);

  if (watch_id < 0) my_abort("Trying inotify_add_watch");

  watch[NOTIFY].fd = notify_fd;
  watch[NOTIFY].events = POLLIN;

  watch[LISTEN].fd = 0; /* STDIN */
  watch[LISTEN].events = POLLIN;
  
  while ((conn = accept(0, NULL, NULL))) {
    watch[CONN].fd = conn;
    watch[CONN].events = POLLIN;
    while (1) {
      if (conn == -1) {
        watch[LISTEN].events = POLLIN;
        poll(watch, CONN, 100);

        if (watch[LISTEN].revents & POLLIN) {
          conn = accept(0, NULL, NULL);
	  if (debug) fprintf(stderr, "Got new connection on listen port\n");
	  watch[CONN].fd = conn;
          watch[CONN].events = POLLIN;
        }
      }
      else {
        watch[LISTEN].events = 0;
        poll(watch, MAX_FDs, 100);

        if (watch[CONN].revents & POLLIN) {
	  if (debug) fprintf(stderr, "Data to read\n");
	  int len = read(conn, ignore, sizeof(ignore));
	  if (debug) fprintf(stderr, "Received and ignored %d bytes\n", len);
	  if (len == 0) {
	    close(conn);
	    conn = -1;
          }
        }
	if (watch[NOTIFY].revents & POLLIN) {
	  if (debug) fprintf(stderr, "Notify fired\n");
	  read(notify_fd, ignore, sizeof(ignore));
	  write(conn, "Change on file\n", 15);
	  if (debug) fprintf(stderr, " Sent update\n");
	}
        if (watch[LISTEN].revents & POLLIN) {
	  fprintf(stderr, "Got a listen with a current socket active\n");
	  close(conn);
          conn = accept(0, NULL, NULL);
	  if (debug) fprintf(stderr, "Got new connection on listen port\n");
	  watch[CONN].fd = conn;
          watch[CONN].events = POLLIN;
        }
      }
    }
  }
}

