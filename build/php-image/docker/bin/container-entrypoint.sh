#!/bin/bash

set -e
set -x

# read shibboleth private key from secrets
if [ ! -f "/etc/shibboleth/sp-key.pem" ] && [ -f "/etc/secrets/sp-key.pem" ]; then
  ln -s  "/etc/secrets/sp-key.pem" "/etc/shibboleth/sp-key.pem"
fi

# configure shibboleth and apache
if [ "${MEMCACHED_SERVICE}" = "" ]; then
  export MEMCACHED_SERVICE=127.0.0.1:11211
fi

envsubst.a8m -no-unset -i /etc/shibboleth/shibboleth2.xml.tmpl -o /etc/shibboleth/shibboleth2.xml

# start Shibboleth or Apache
if [ "$1" = "shibd" -or "$1" = "shibboleth" ]; then
    exec shibd -f -F
elif [ "$1" = "apache" ]; then
    exec apache2-foreground
else
    echo "Wrong agument given. Only apache or shibboleth is possible."
    exit 1
fi
