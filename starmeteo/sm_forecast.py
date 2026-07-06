#!/usr/bin/env python3
'''
starmeteo reverse engeneering project

This tool generate a StarMeteo compatible message for weather forecast.
It can use different weather forcast backend.
'''

import argparse
import requests
from sm_utils import *
from datetime import datetime


def om_geocode_city(city_name: str):
    url = "https://geocoding-api.open-meteo.com/v1/search"
    r = requests.get(url, params={"name": city_name, "count": 1, "language": "en", "format": "json"}, timeout=30)
    r.raise_for_status()
    data = r.json()
    if not data.get("results"):
        raise RuntimeError(f"No geocoding results for {city_name!r}")
    best = data["results"][0]
    return best["latitude"], best["longitude"], best.get("timezone"), best.get("name")

def om_fetch_5day(lat: float, lon: float, timezone: str | None = None):
    url = "https://api.open-meteo.com/v1/forecast"
    params = {
        "latitude": lat,
        "longitude": lon,
        "daily": "temperature_2m_max,temperature_2m_min,precipitation_sum,weather_code",
        "forecast_days": 5,
        "temperature_unit": "celsius",
        "timezone": timezone or "auto",
    }
    r = requests.get(url, params=params, timeout=30)
    r.raise_for_status()
    return r.json()

def om_weather_code_to_starmeteo(omcode):
  # see https://open-meteo.com/en/docs#weather_variable_documentation
  # based on https://www.nodc.noaa.gov/archive/arc0021/0002199/1.1/data/0-data/HTML/WMO-CODE/WMO4677.HTM
  # and https://gist.github.com/stellasphere/9490c195ed2b53c707087c8c2db4ec0c
  if omcode == 0:
    return "clearsky", 0x000
  elif omcode == 1:
    return "mainlyclear", 0x001
  elif omcode == 2:
    return "partlycloudy", 0x002
  elif omcode == 3:
    return "cloudyovercast",  0x003
  elif omcode == 45:
    return "fog", 0x003 # no fog in starmeto
  elif omcode == 48:
    return "depositingfog",  0x003 # no fog in starmeto
  elif omcode == 51:
    return "drizzlelight",  0x003 # no drizzle in starmeto
  elif omcode == 53:
    return "drizzlemoderate",  0x003 # no drizzle in starmeto
  elif omcode == 55:
    return "drizzleintense", 0x100
  elif omcode == 56:
    return "drizzlefreezlight", 0x100
  elif omcode == 57:
    return "drizzlefreezzintense", 0x100
  elif omcode == 61:
    return "rainlight", 0x040
  elif omcode == 63:
    return "rainmoderate", 0x041
  elif omcode == 65:
    return "rainintense", 0x042
  elif omcode == 66:
    return "rainfreeze", 0x100
  elif omcode == 67:
    return "rainfreezeheavy", 0x100
  elif omcode == 71:
    return "snowlight", 0x100
  elif omcode == 73:
    return "snowmoderate", 0x101
  elif omcode == 75:
    return "snowintense", 0x101
  elif omcode == 77:
    return "snowgrains", 0x141
  elif omcode == 80:
    return "rainshowerlight", 0x042
  elif omcode == 81:
    return "rainshowermoderate", 0x042
  elif omcode == 82:
    return "rainshowerviolent", 0x0C0
  elif omcode == 85:
    return "snowshowerlight", 0x100
  elif omcode == 86:
    return "snowshowerheavy", 0x101
  elif omcode == 95:
    return "thunderstormlight", 0x082
  elif omcode == 96:
    return "thunderstormlighthail", 0x083
  elif omcode == 99:
    return "thunderstormheavyhail", 0x0C0



# get from open-meteo.com
def get_openmeteo(v, location):
    debug(v, f"Geocoding location : {location} ...")
    geocode = om_geocode_city(location)
    debug(v, f"Found location : {geocode}")
    lat, lon, tz, place_name = geocode
    debug(v, "Fetching forecast...")
    data = om_fetch_5day(lat, lon, tz)
    daily = data["daily"]
    dates = daily["time"]
    tmax = daily["temperature_2m_max"]
    tmin = daily["temperature_2m_min"]
    prcp = daily["precipitation_sum"]
    wc = daily["weather_code"]

    print(f"5-day forecast for {place_name} ({lat:.4f},{lon:.4f}) timezone={data.get('timezone')}")
    for i in range(len(dates)):
        sm = om_weather_code_to_starmeteo(wc[i])
        print(f"{dates[i]}: {tmin[i]:.1f}..{tmax[i]:.1f} °C, precip {prcp[i]:.1f} mm, weathercode {wc[i]}, startmeteo {sm}")



def main():
    parser = argparse.ArgumentParser(description="This tool generate a StarMeteo compatible message for weather forcast.")
    parser.add_argument("--verbose", action="store_true", 
      help="Enable debug output")
    
    parser.add_argument("--backend","-b",
      help="Indicates which weather forecast backend to use. Can be \"openmeteo\", \"weatherunderground\", or \"none\"",
      choices=["weatherunderground", "wu", "openmeteo", "om", "none"],        
      default="none"
    )
    parser.add_argument("--location","-l",
      help="Indicates the location to get weather for (eg : \"Paris, France\")",
      default="none"
    )
    
    parser.add_argument("--wu-api",        
      dest="wu_api", required=False, type=str,        
      help="Weather Underground API key (required when --backend=weatherunderground).")
    
    args = parser.parse_args()
    
    if args.backend == "wu":
      args.backend = "weatherunderground"
    if args.backend == "om":
      args.backend = "openmeteo"
    if args.backend == "weatherunderground":        
      if not args.wu_api:            
        error('Missing --wu-api. It is required when --backend="weatherunderground".')
        return(1)
      debug(args.verbose, "Using Weather Underground backend.")
    
    if args.backend == "openmeteo":
      forecast = get_openmeteo(args.verbose, args.location)
    elif args.backend == "weatherunderground":
      forecast = get_weatherunderground(args.verbose, args.location, args.wu_api)
    elif args.backend == "none":
      forecast = get_local_forecast(args.verbose, args)
    else:
      error("Wrong backend")
      return(2)

    

if __name__ == "__main__":
    main()
