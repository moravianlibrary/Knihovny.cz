
# Containers


### vufind-php

* contains Apache + PHP + shibboleth

Mountpoint:

* `/var/www/html`

Shibboleth expected secrets:
* `/etc/secrets/sp-key.pem`

* /etc/apache2/sites-enabled/000-default.conf
* /etc/apache2/sites-enabled/099-server-status.conf
  * provide server-status on port :8117

Environment variables:

* `APACHE_LOG_DIR`
* `APACHE_SITE_NAME`

### vufind-app



## TODO:

* add shibboleth configs
* add apache configs

