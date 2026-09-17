#!/usr/bin/env python3

import shutil
import subprocess
import sys
import time

import cv2
import keyboard
import numpy as np
from PIL import Image, ImageDraw, ImageOps
'''
list camera : 
ffmpeg -list_devices true -f dshow -i dummy
'''

URL = "https://192.168.1.106:4444/video/h264"
CAMERA_NAME = "WCMR_MJPEG-CAM"

SOURCE_WIDTH = 720
SOURCE_HEIGHT = 540
SOURCE_FPS = 10
FRAME_SIZE = SOURCE_WIDTH * SOURCE_HEIGHT

RECT_X1 = 440
RECT_Y1 = 301
RECT_X2 = 476
RECT_Y2 = 331

PIXEL_DIFF_THRESHOLD = 10
RECT_MOVE_STEP = 4
KEY_REPEAT_DELAY = 0.05

WINDOW_NAME = "Detection de mouvement"

SNAPSHOT_REFERENCE = "snapshot_ref.png"
SNAPSHOT_CURRENT = "snapshot.png"
SNAPSHOT_INTERVAL = 10

DETECTION_PCENT_THRESHOLD=17


def read_exact(stream, size):
    data = bytearray()

    while len(data) < size:
        chunk = stream.read(size - len(data))

        if not chunk:
            return None

        data.extend(chunk)

    return bytes(data)


def shift_is_pressed():
    return (
        keyboard.is_pressed("shift")
        or keyboard.is_pressed("left shift")
        or keyboard.is_pressed("right shift")
    )


def move_rectangle(image_width, image_height, last_move_time):
    global RECT_X1
    global RECT_Y1
    global RECT_X2
    global RECT_Y2

    current_time = time.perf_counter()

    if current_time - last_move_time < KEY_REPEAT_DELAY:
        return False, last_move_time

    moved = False

    if shift_is_pressed():
        if keyboard.is_pressed("left"):
            new_value = max(
                RECT_X1 + 1,
                RECT_X2 - RECT_MOVE_STEP
            )

            if new_value != RECT_X2:
                RECT_X2 = new_value
                moved = True

        elif keyboard.is_pressed("right"):
            new_value = min(
                image_width,
                RECT_X2 + RECT_MOVE_STEP
            )

            if new_value != RECT_X2:
                RECT_X2 = new_value
                moved = True

        elif keyboard.is_pressed("up"):
            new_value = max(
                RECT_Y1 + 1,
                RECT_Y2 - RECT_MOVE_STEP
            )

            if new_value != RECT_Y2:
                RECT_Y2 = new_value
                moved = True

        elif keyboard.is_pressed("down"):
            new_value = min(
                image_height,
                RECT_Y2 + RECT_MOVE_STEP
            )

            if new_value != RECT_Y2:
                RECT_Y2 = new_value
                moved = True

    else:
        if keyboard.is_pressed("left"):
            new_value = max(
                0,
                RECT_X1 - RECT_MOVE_STEP
            )

            if new_value != RECT_X1:
                RECT_X1 = new_value
                moved = True

        elif keyboard.is_pressed("right"):
            new_value = min(
                RECT_X2 - 1,
                RECT_X1 + RECT_MOVE_STEP
            )

            if new_value != RECT_X1:
                RECT_X1 = new_value
                moved = True

        elif keyboard.is_pressed("up"):
            new_value = max(
                0,
                RECT_Y1 - RECT_MOVE_STEP
            )

            if new_value != RECT_Y1:
                RECT_Y1 = new_value
                moved = True

        elif keyboard.is_pressed("down"):
            new_value = min(
                RECT_Y2 - 1,
                RECT_Y1 + RECT_MOVE_STEP
            )

            if new_value != RECT_Y1:
                RECT_Y1 = new_value
                moved = True

    if moved:
        last_move_time = current_time

        print(
            f"Rectangle : "
            f"({RECT_X1}, {RECT_Y1}) -> "
            f"({RECT_X2}, {RECT_Y2}) | "
            f"taille={RECT_X2 - RECT_X1}x{RECT_Y2 - RECT_Y1}"
        )

    return moved, last_move_time


def validate_rectangle(image_width, image_height):
    if RECT_X1 < 0 or RECT_Y1 < 0:
        raise ValueError(
            "RECT_X1 et RECT_Y1 doivent etre positifs ou nuls"
        )

    if RECT_X2 > image_width or RECT_Y2 > image_height:
        raise ValueError(
            "Le rectangle initial depasse les limites de l'image"
        )

    if RECT_X1 >= RECT_X2:
        raise ValueError(
            "RECT_X1 doit etre inferieur a RECT_X2"
        )

    if RECT_Y1 >= RECT_Y2:
        raise ValueError(
            "RECT_Y1 doit etre inferieur a RECT_Y2"
        )


def compare_rois(current_roi, previous_roi):
    current_pixels = current_roi.tobytes()
    total_pixels = len(current_pixels)

    if previous_roi is None:
        return 0, total_pixels, 0.0

    if previous_roi.size != current_roi.size:
        return 0, total_pixels, 0.0

    previous_pixels = previous_roi.tobytes()

    different_pixels = sum(
        1
        for current_pixel, previous_pixel in zip(
            current_pixels,
            previous_pixels
        )
        if abs(
            current_pixel - previous_pixel
        ) > PIXEL_DIFF_THRESHOLD
    )

    if total_pixels == 0:
        difference_percent = 0.0
    else:
        difference_percent = (
            different_pixels * 100.0 / total_pixels
        )

    return (
        different_pixels,
        total_pixels,
        difference_percent
    )


def create_display_image(
    img_gray,
    difference_percent,
    different_pixels,
    total_pixels,
    current_fps
):
    display_image = img_gray.convert("RGB")
    draw = ImageDraw.Draw(display_image)

    draw.rectangle(
        [
            RECT_X1,
            RECT_Y1,
            RECT_X2 - 1,
            RECT_Y2 - 1
        ],
        outline=(0, 255, 0),
        width=2
    )

    rectangle_width = RECT_X2 - RECT_X1
    rectangle_height = RECT_Y2 - RECT_Y1

    difference_text = (
        f"Difference : {difference_percent:.2f}%  "
        f"Pixels : {different_pixels}/{total_pixels}"
    )

    rectangle_text = (
        f"ROI : ({RECT_X1},{RECT_Y1}) -> "
        f"({RECT_X2},{RECT_Y2})  "
        f"Taille : {rectangle_width}x{rectangle_height}"
    )

    controls_text = (
        f"FPS : {current_fps:.2f}  "
        f"Fleches : haut-gauche  "
        f"Maj+Fleches : bas-droit"
    )

    draw.rectangle(
        [5, 5, 535, 78],
        fill=(0, 0, 0)
    )

    draw.text(
        (10, 10),
        difference_text,
        fill=(0, 255, 0)
    )

    draw.text(
        (10, 34),
        rectangle_text,
        fill=(0, 255, 0)
    )

    draw.text(
        (10, 58),
        controls_text,
        fill=(0, 255, 0)
    )

    return display_image


def main():
    rotated_width = SOURCE_HEIGHT
    rotated_height = SOURCE_WIDTH

    validate_rectangle(
        rotated_width,
        rotated_height
    )

    ffmpeg_path = shutil.which("ffmpeg")

    if ffmpeg_path is None:
        print(
            "Erreur : ffmpeg est introuvable dans le PATH.",
            file=sys.stderr
        )
        return 1

    ffmpeg_command = [
        ffmpeg_path,
        "-hide_banner",
        "-loglevel", "error",
        #"-tls_verify", "0",
        #"-fflags", "nobuffer",
        #"-flags", "low_delay",
        #"-f", "h264",
        #"-i", URL,
        "-f", "dshow",
        "-i", f"video={CAMERA_NAME}",
        "-an",
        "-vf",
        (
            f"scale={SOURCE_WIDTH}:{SOURCE_HEIGHT},"
            f"fps={SOURCE_FPS},"
            f"format=gray"
        ),
        "-f", "rawvideo",
        "-pix_fmt", "gray",
        "pipe:1"
    ]

    creation_flags = 0

    if sys.platform == "win32":
        creation_flags = subprocess.CREATE_NO_WINDOW

    process = None
    window_created = False
    return_code = 0

    try:
        process = subprocess.Popen(
            ffmpeg_command,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            bufsize=FRAME_SIZE * 2,
            creationflags=creation_flags
        )

        frame_number = 0
        previous_roi = None
        last_move_time = 0.0

        fps_start_time = time.perf_counter()
        fps_frame_count = 0
        current_fps = 0.0

        cv2.namedWindow(
            WINDOW_NAME,
            cv2.WINDOW_NORMAL
        )

        cv2.resizeWindow(
            WINDOW_NAME,
            rotated_width,
            rotated_height
        )

        window_created = True
        

        while True:
            if process.stdout is None:
                raise RuntimeError(
                    "La sortie standard de ffmpeg est indisponible"
                )

        
            read_start = time.perf_counter()

            frame_data = read_exact(
                process.stdout,
                FRAME_SIZE
            )

            decode_time = time.perf_counter() - read_start
            NOW = time # The time when we get this frame

            if frame_data is None:
                ffmpeg_error = ""

                if process.stderr is not None:
                    error_data = process.stderr.read()

                    ffmpeg_error = error_data.decode(
                        "utf-8",
                        errors="replace"
                    ).strip()

                if ffmpeg_error:
                    raise RuntimeError(
                        f"Flux interrompu : {ffmpeg_error}"
                    )

                raise RuntimeError(
                    "Le flux H.264 a ete interrompu"
                )

            processing_start = time.perf_counter()

            img_gray = Image.frombytes(
                "L",
                (SOURCE_WIDTH, SOURCE_HEIGHT),
                frame_data
            )

            img_gray = ImageOps.equalize(img_gray)

            img_gray = img_gray.point(
                lambda pixel: pixel & 0b11000000
            )

            img_gray = img_gray.rotate(
                -90,
                expand=True
            )

            image_width, image_height = img_gray.size

            rectangle_moved, last_move_time = move_rectangle(
                image_width,
                image_height,
                last_move_time
            )

            if rectangle_moved:
                previous_roi = None

            current_roi = img_gray.crop(
                (
                    RECT_X1,
                    RECT_Y1,
                    RECT_X2,
                    RECT_Y2
                )
            )

            (
                different_pixels,
                total_pixels,
                difference_percent
            ) = compare_rois(
                current_roi,
                previous_roi
            )

            previous_roi = current_roi.copy()

            frame_number += 1
            fps_frame_count += 1

            fps_elapsed = (
                time.perf_counter() - fps_start_time
            )

            if fps_elapsed >= 1.0:
                current_fps = (
                    fps_frame_count / fps_elapsed
                )

                fps_start_time = time.perf_counter()
                fps_frame_count = 0

            display_image = create_display_image(
                img_gray,
                difference_percent,
                different_pixels,
                total_pixels,
                current_fps
            )

            processing_time = (
                time.perf_counter() - processing_start
            )

            cv_image = cv2.cvtColor(
                np.asarray(display_image),
                cv2.COLOR_RGB2BGR
            )

            cv2.imshow(
                WINDOW_NAME,
                cv_image
            )

            print(
                f"Frame {frame_number:6d} | "
                f"diff={difference_percent:6.2f}% | "
                f"pixels={different_pixels:4d}/{total_pixels:4d} | "
                f"ROI=({RECT_X1},{RECT_Y1})"
                f"-({RECT_X2},{RECT_Y2}) | "
                f"lecture={decode_time:.3f}s | "
                f"traitement={processing_time:.3f}s | "
                f"fps={current_fps:.2f}"
            )

            # Motion detected
            if (difference_percent > DETECTION_PCENT_THRESHOLD):
                print("move")

            if frame_number == 1:
                display_image.save(
                    SNAPSHOT_REFERENCE
                )

            elif frame_number % SNAPSHOT_INTERVAL == 0:
                display_image.save(
                    SNAPSHOT_CURRENT
                )

            key = cv2.waitKey(1) & 0xFF

            if key == 27:
                break

            if key in (ord("q"), ord("Q")):
                break

            try:
                if cv2.getWindowProperty(
                    WINDOW_NAME,
                    cv2.WND_PROP_VISIBLE
                ) < 1:
                    break

            except cv2.error:
                break

    except KeyboardInterrupt:
        pass

    except Exception as error:
        print(
            f"Erreur : {error}",
            file=sys.stderr
        )

        return_code = 1

    finally:
        if window_created:
            try:
                cv2.destroyAllWindows()
                cv2.waitKey(1)
            except cv2.error:
                pass

        if process is not None:
            if process.stdout is not None:
                try:
                    process.stdout.close()
                except Exception:
                    pass

            if process.stderr is not None:
                try:
                    process.stderr.close()
                except Exception:
                    pass

            if process.poll() is None:
                process.terminate()

                try:
                    process.wait(timeout=2)

                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait()

    return return_code


if __name__ == "__main__":
    sys.exit(main())