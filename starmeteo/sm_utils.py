def debug(verbose: bool, msg: str):
    if verbose:
        print(f"[DEBUG] {msg}")

def error(msg: str):
    print(f"[ERROR] {msg}")
