Based on https://gist.github.com/x2q/5124616

wget https://gist.githubusercontent.com/x2q/5124616/raw/bf21dbda4a67de2c2d15d6c66b1e1bd0b1db7e1b/usbreset.c
gcc -o usbreset usbreset.c
sudo mv usbreset /usr/sbin/
