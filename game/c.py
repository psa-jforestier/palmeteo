# -*- coding: utf-8 -*-
import curses
import pprint

def wallToStr(w):
	s = u''
	for c in unicode(w, 'utf-8'):
		if c == '\n':
			c = '\r\n'
		s = s + c
	return s
	
try:
	stdscr = curses.initscr() 
	curses.noecho()
	curses.cbreak() # react to keys instantly
	stdscr.keypad(True) # Allow the app to catch special key 
	#stdscr.clear()
	#stdscr.refresh()
	'''
	Labyrinthe
	179	│
	180	┤
	191	┐
	192	└
	193	┴
	194	┬
	195	├
	196	─
	197	┼
	217	┘
	218	┌
	
	'''
	walls = {
	#     HBGD
		0b0000:'...\n...\n...',
		0b0001:'..▐\n..▐\n..▐',
		0b0010:'▌..\n▌..\n▌..',
		0b0011:'▌.▐\n▌.▐\n▌.▐',
		0b0100:'...\n...\n▄▄▄',
		0b0101:'..▐\n..▐\n▄▄▟',
		0b0110:'▌..\n▌..\n▙▄▄',
		0b0111:'▌.▐\n▌.▐\n▙▄▟',
		0b1000:'▀▀▀\n...\n...',
		0b1001:'▀▀▜\n..▐\n..▐',
		0b1010:'▛▀▀\n▌..\n▌..',
		0b1011:'▛▀▜\n▌.▐\n▌.▐',
		0b1100:'▀▀▀\n...\n▄▄▄',
		0b1101:'▀▀▜\n..▐\n▄▄▟',
		0b1110:'▛▀▀\n▌..\n▙▄▄',
		0b1111:'▛▀▜\n▌.▐\n▙▄▟',
		
	}
	for (i,j) in walls.items():
		print i,"\r\n",wallToStr(j)
		print "\r\n"
	area = {
	}

finally:
	curses.nocbreak()
	stdscr.keypad(False)
	curses.echo()
	curses.endwin()