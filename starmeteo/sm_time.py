#!/usr/bin/env python3
'''
starmeteo reverse engeneering project

This tool generate a StarMeteo compatible message for time synchronization.
'''

import argparse
from sm_utils import *
from datetime import datetime



    
def sm_encode_datetime(dt):
  # TODO
  return dt.strftime("%Y-%m-%d_%H:%M:%S")


def main():
    parser = argparse.ArgumentParser(description="This tool generate a StarMeteo compatible message for time synchronization.")
    parser.add_argument("--verbose", action="store_true", help="Enable debug output")
    parser.add_argument("--time", dest="time", required=False, 
      type=str, 
      help="Time & date value as a string")
    parser.add_argument("--timeformat", dest="timeformat", required=False, 
      type=str, 
      default="%Y-%m-%d_%H:%M:%S",
      help="Time & date format. Use Python datetime.strptime format. Default is %%Y-%%m-%%d_%%H:%%M:%%S. If you use the \"date -R\" Linux command, use format \"%%a, %%d %%b %%Y %%H:%%M:%%S %%z\" (do not forget the \")")
    args = parser.parse_args()

    dt = args.time
    if (dt == None):
      dt = datetime.now()      
    else:
      dt = datetime.strptime(args.time, args.timeformat)
    debug(args.verbose, f"Time is : {dt}")
    encoded_dt = sm_encode_datetime(dt)
    if (dt == None):
      error("Unable to convert date time to StarMeteo")
      return(1)
    else:
      print(encoded_dt)

if __name__ == "__main__":
    main()
