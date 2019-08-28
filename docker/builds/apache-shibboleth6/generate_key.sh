#!/usr/bin/env bash

# Generate self-signed SSL/TLS certificates
#  - https://stackoverflow.com/questions/10175812/how-to-create-a-self-signed-certificate-with-openssl

KEY_FILE="$1"
CRT_FILE="$2"
CN="$3"
SSL_VALIDITY_DAYS="$4"

if test -f "$KEY_FILE"; then
    exit 0
fi

openssl req \
    -x509 \
    -newkey rsa:4096 \
    -nodes \
    -keyout "$KEY_FILE" \
    -out "$CRT_FILE" \
    -days "$SSL_VALIDITY_DAYS" \
    -subj "/CN=${CN}"
