#!/bin/sh

g++ findcontour2json.cpp -o findcontour2json `env PKG_CONFIG_PATH=/usr/local/lib/pkgconfig/ pkg-config --cflags --libs opencv`
