# -*- coding: utf-8 -*-
import curses
import pprint
import random 
import time
import binascii
import math
import struct
from curses import wrapper
import locale

locale.setlocale(locale.LC_ALL, '')    # set your locale

def microtime(get_as_float = False) :
    if get_as_float:
        return time.time()
    else:
        return '%f %d' % math.modf(time.time())

 
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
	stdscr.clear()
	stdscr.refresh()
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
	# █
	walls = {
		# 
		0b0000:['  ','  '], 
		0b0001:[' #',' #'], 
		0b0010:['# ','# '], 
		0b0011:['##','  '],
		0b0100:['  ','##'],
		0b0101:[' #','  '],
		0b0110:['  ','  '],
		0b0111:['  ',' #'],
		0b1000:['##','##']
		
		#0b0101:['##','##'],
		#0b0110:['# ',' #'],
		#0b0111:['  ','  '],
	}
	nb_of_wall = len(walls)
	'''
	for (i,j) in walls.items():
		print i,"\r\n",wallToStr(j)
		print "\r\n"
	'''
	random.seed(0)
	w = 50
	h = 10
	area = [0] * h
	x = 1
	y = 0
	for i in range(0,h):
		area[i] = [0] * w
		for j in range(0, w):
			cell = ((x+j) * (y+i)) % 6
			area[i][j] = cell
	
	def areaToString(area, areaW, areaH):
		'''
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
		'''
		lines = ''
		a = [None]* (2 * areaH)
		for i in range(0, areaH):
			line1 = ''
			line2 = ''
			for j in range(0, areaW):
				cell = area[i][j]
				wall = walls[cell]
				line1 = line1 + wall[0]
				line2 = line2 + wall[1]
			#print line1,"\r\n",line2,"\r"
			lines = lines + line1 +  "\r\n" + line2 + "\r\n"
		return lines
	
	random.seed(0)
	x = 0
	y = 0
	while True:
		w = 32
		h = 16
		area = [0] * h
		T = microtime(True)
		for i in range(0,h):
			area[i] = [0] * w
			for j in range(0, w):
				xx = x + j
				yy = y + i
				cell_id = str(x+j) + "," +str(y+i)
				#cell_hash = binascii.crc32(cell_id, 0)
				#cell_hash = ((x+j) * (y+i))
				#cell_hash = x + j + y + i
				#cell_hash = binascii.crc_hqx(struct.pack('<qqqq',x+j,y+i,x+j,y+i), 0)
				#cell_hash = (xx & 0xFF) |  (yy & 0xff)
				cell_hash = binascii.crc_hqx(
					struct.pack('<qq',xx & 0x0ffffffff,yy&0xffffffff), 
					0)
				cell = cell_hash % nb_of_wall
				area[i][j] = cell
		stdscr.clear()		
		stdscr.addstr(areaToString(area, w, h))
		stdscr.refresh()
		#stdscr.refresh()
		print "x=",x," y=",y, "last cell_h=", cell_hash, ' T=', microtime(True) - T
		time.sleep(0.2)
		x = x + 1
		#y = y + 1
	

finally:
	curses.nocbreak()
	stdscr.keypad(False)
	curses.echo()
	curses.endwin()