# -*- coding: utf-8 -*-
import curses
import pprint
import random 
 
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
		0b0000:['...', '...', '...'],
		0b0001:['..▐', '..▐', '..▐'],
		0b0010:['▌..', '▌..', '▌..'],
		0b0011:['▌.▐', '▌.▐', '▌.▐'],
		0b0100:['...', '...', '▄▄▄'],
		0b0101:['..▐', '..▐', '▄▄▟'],
		0b0110:['▌..', '▌..', '▙▄▄'],
		0b0111:['▌.▐', '▌.▐', '▙▄▟'],
		0b1000:['▀▀▀', '...', '...'],
		0b1001:['▀▀▜', '..▐', '..▐'],
		0b1010:['▛▀▀', '▌..', '▌..'],
		0b1011:['▛▀▜', '▌.▐', '▌.▐'],
		0b1100:['▀▀▀', '...', '▄▄▄'],
		0b1101:['▀▀▜', '..▐', '▄▄▟'],
		0b1110:['▛▀▀', '▌..', '▙▄▄'],
		0b1111:['▛▀▜', '▌.▐', '▙▄▟'],
		
	}
	walls = {
	#     HBGD
		0b0000:['   ', ' o ', '   '],
		0b0001:['  ▐', ' o▐', '  ▐'],
		0b0010:['▌  ', '▌o ', '▌  '],
		0b0011:['▀▀▀', ' o ', '   '],
		0b0100:['   ', ' o ', '▄▄▄'],

		
	}
	'''
	for (i,j) in walls.items():
		print i,"\r\n",wallToStr(j)
		print "\r\n"
	'''
	random.seed(0)
	w = 20
	h = 10
	area = [0] * h
	for i in range(0,h):
		area[i] = [0] * w
		for j in range(0, w):
			area[i][j] = (i * j ) % 5
	
	def areaToString(area, areaW, areaH):
		a = [None] * (3 * areaH)
		for i in range(0,areaH):
			line1 = ''
			line2 = ''
			line3 = ''
			for j in range(0, areaW):
				cell = area[i][j]
				wall = walls[cell]
				line1 = line1 + wall[0]
				line2 = line2 + wall[1]
				line3 = line3 + wall[2]
			print line1,"\r\n",line2,"\r\n",line3,"\r"
		
	
	print areaToString(area, w, h)

finally:
	curses.nocbreak()
	stdscr.keypad(False)
	curses.echo()
	curses.endwin()