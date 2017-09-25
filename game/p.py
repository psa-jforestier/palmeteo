import curses

try:
	stdscr = curses.initscr()
	curses.cbreak()
	curses.noecho()
	stdscr.keypad(1)

	stdscr.addstr(0,10,"Hit 'q' to quit")
	stdscr.refresh()

	key = ''
	while key != ord('q'):
		curses.setsyx(0,0)
		curses.doupdate()
		key = stdscr.getch()
		stdscr.addch(20,25,key)
		stdscr.refresh()
		if key == curses.KEY_UP: 
			stdscr.addstr(2, 20, "Up")
		elif key == curses.KEY_DOWN: 
			stdscr.addstr(3, 20, "Down")
		elif key == curses.KEY_LEFT: 
			stdscr.addstr(4, 20, "Left")
		elif key == curses.KEY_RIGHT: 
			stdscr.addstr(5, 20, "Right")
finally:
	# Set everything back to normal
	stdscr.keypad(0)
	curses.echo()
	curses.nocbreak()
	curses.endwin()
