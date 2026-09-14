#!/usr/bin/env python3
"""Generate a valid Star Météo time frame.

This script mirrors the time-frame encoding logic from src/pocsag/starmeteo.c,
with command-line handling for the time and area list.
"""

import argparse
import datetime as dt
import sys

MAX_AREAS = 18
DEFAULT_AREAS = [75, 77, 78, 91, 92, 93, 94, 95]
MAX_MSG_SIZE = 512


def raw2char(value):
    """Encode a 6-bit raw value into the printable Starmeteo ASCII alphabet."""
    value = int(value) & 0x3F
    if 59 <= value <= 63:
        return chr(ord('k') + (value - 59))
    if value == 0x20:
        return 'p'
    if value == 0x03:
        return 's'
    return chr(value + 0x20)


def set_quartet(dcodefrm, idx, quartet):
    """Pack a 4-bit quartet into the 6-bit raw encoding array."""
    bitidx = idx * 4
    for j in range(4):
        i = bitidx // 6
        bitpos = 5 - (bitidx % 6)
        mask = 1 << bitpos
        if quartet & (0x8 >> j):
            dcodefrm[i] |= mask
        else:
            dcodefrm[i] &= ~mask
        bitidx += 1


def quartets_to_ascii(quartets):
    """Convert a list of quartets to the printable ASCII message used by Starmeteo."""
    dcodefrm = [0] * (((len(quartets) * 4) + 5) // 6)
    for idx, quartet in enumerate(quartets):
        set_quartet(dcodefrm, idx, quartet)

    size = (len(quartets) * 4) // 6
    return ''.join(raw2char(dcodefrm[i]) for i in range(size))


def parse_time(value):
    """Parse the time string in YYYY-MM-DD:HH:MM:SS format."""
    if value is None or value == 'NOW':
        return dt.datetime.now()
    return dt.datetime.strptime(value, '%Y-%m-%d:%H:%M:%S')


def encode_time_frame(tm):
    """Encode the Starmeteo time block without the area list, matching the C implementation."""
    quartets = [0] * 9
    quartets[0] = 0xF
    year_code = tm.year - 2000

    if tm.hour < 10:
        quartets[1] = tm.hour
        quartets[2] = tm.minute // 10
        quartets[3] = tm.minute % 10
    else:
        if 10 <= tm.hour <= 19:
            quartets[1] = tm.hour - 10
            quartets[2] = (tm.minute // 10) + 10
            quartets[3] = tm.minute % 10
        else:
            quartets[1] = tm.hour - 10
            quartets[2] = tm.minute // 10
            quartets[3] = tm.minute % 10

    quartets[4] = tm.month
    quartets[5] = (((tm.day // 10) & 3) << 2) | (((tm.day % 10) >> 2) & 3)
    quartets[6] = (((tm.day % 10) & 3) << 2) | (((year_code >> 4) & 0x3))
    quartets[7] = year_code & 0xF

    checksum = 0x7
    for q in quartets[:8]:
        checksum += q
    quartets[8] = checksum & 0xF
    return quartets


def set_field(quartetfrm, bitidx, fieldsize, data):
    """Set arbitrary bit-sized data into the quartet array, matching starmeteo.c."""
    j = 0
    while j < fieldsize and ((bitidx >> 2) < len(quartetfrm)):
        bit = (data >> ((fieldsize - j) - 1)) & 1
        qidx = bitidx >> 2
        bit_in_q = bitidx & 3
        mask = 0x8 >> bit_in_q
        if bit:
            quartetfrm[qidx] |= mask
        else:
            quartetfrm[qidx] &= ~mask
        bitidx += 1
        j += 1
    return bitidx


def encode_time(value=None, areas=None, interval=12, verbose=False):
    """Return the printable Starmeteo time-frame payload matching the C encoder."""
    if value is None or value == 'NOW':
        tm = dt.datetime.now()
    else:
        tm = parse_time(value)

    if areas is None:
        areas = list(DEFAULT_AREAS)
    if len(areas) > MAX_AREAS:
        raise ValueError(f"area count cannot exceed {MAX_AREAS}")

    time_quartets = encode_time_frame(tm)
    if verbose:
        print(f"Time: {tm.strftime('%Y-%m-%d:%H:%M:%S')}")
        print(f"Time quartets: {[hex(q) for q in time_quartets]}")

    # Port the C algorithm directly: reserve a fixed-size quartet buffer, keep a
    # separate quartet count, and write the area metadata at the exact bit offset
    # used in the reference implementation.
    quartetfrm = [0] * (MAX_MSG_SIZE * 3)
    for idx, q in enumerate(time_quartets):
        quartetfrm[idx] = q

    quartets_cnt = len(time_quartets)
    quartetfrm[quartets_cnt] = 0x0
    quartetfrm[quartets_cnt + 1] = 0x0
    quartetfrm[quartets_cnt + 2] = 0x0
    quartets_cnt += 3

    bitidx = quartets_cnt * 4
    bitidx = set_field(quartetfrm, bitidx, 5, interval)
    bitidx = set_field(quartetfrm, bitidx, 5, len(areas))
    for area in areas:
        bitidx = set_field(quartetfrm, bitidx, 7, int(area))

    if bitidx & 3:
        bitidx = set_field(quartetfrm, bitidx, (4 - (bitidx & 3)), 0)

    i = bitidx >> 2
    quartetfrm[i] = 0x1
    i += 1
    sumv = 0x7
    j = 9
    while j < i:
        sumv += quartetfrm[j]
        j += 1
    quartetfrm[i] = (sumv >> 4) & 0xF
    i += 1
    quartetfrm[i] = sumv & 0xF
    i += 1
    quartetfrm[i] = 0x0
    i += 1
    quartetfrm[i] = 0x0
    i += 1
    quartets_cnt = i

    if verbose:
        print(f"Area list: {areas}")
        print(f"Final quartets: {[hex(q) for q in quartetfrm[:quartets_cnt]]}")

    dcodefrm = [0] * (((quartets_cnt * 4) + 5) // 6)
    for idx in range(quartets_cnt):
        set_quartet(dcodefrm, idx, quartetfrm[idx])

    size = (quartets_cnt * 4) // 6
    payload = [raw2char(dcodefrm[i]) for i in range(size)]
    if len(payload) > 7 and payload[6] == ' ' and payload[7] == ' ':
        del payload[7]
    return ''.join(payload)


def build_parser():
    parser = argparse.ArgumentParser(
        description='Encode a valid Star Météo time frame.',
        formatter_class=argparse.RawTextHelpFormatter,
        epilog='Examples:\n'
               '  starmeteo.py\n'
               '  starmeteo.py --time=2026-09-14:18:30:00 --area=75,91,92\n'
               '  starmeteo.py --time=2026-09-14:18:30:00 --area=75 --verbose -n'
    )
    parser.add_argument('--time', nargs='?', const='NOW', default=None,
                        help='time to encode in YYYY-MM-DD:HH:MM:SS format; defaults to current time')
    parser.add_argument('--area', default=None,
                        help='comma-separated list of areas/departments (max 18); default matches the C encoder defaults')
    parser.add_argument('--interval', default=12, type=int,
                        help='interval in minutes to wake up the station to get forecast')
    parser.add_argument('-n', action='store_true', help='do not print the trailing newline')
    parser.add_argument('--verbose', action='store_true', help='print debugging information')
    return parser


def parse_areas(raw_value):
    if raw_value is None or raw_value == '':
        return list(DEFAULT_AREAS)
    parts = [p.strip() for p in str(raw_value).split(',') if p.strip()]
    if not parts:
        return list(DEFAULT_AREAS)
    values = [int(p) for p in parts]
    if len(values) > MAX_AREAS:
        raise ValueError(f"Maximum {MAX_AREAS} areas allowed")
    return values


def main(argv=None):
    parser = build_parser()
    args = parser.parse_args(argv)

    areas = parse_areas(args.area)
    payload = encode_time(args.time, areas, args.interval, verbose=args.verbose)

    if args.verbose:
        print(f"ASCII payload: {payload}")

    if args.n:
        sys.stdout.write(payload)
    else:
        sys.stdout.write(payload + '\n')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
