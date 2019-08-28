### What is this?

It is an minimalistic, yet powerful & productive [docker](https://www.docker.com/) build based on the official [php7](https://hub.docker.com/_/php/) docker repository with an [Apache httpd](https://httpd.apache.org/) server included.

Base distribution is [Debian "jessie"](https://www.debian.org/releases/jessie/). Very stable, secure & favorite for server applications.

There are bundled these `php` extensions:

 - pdo_mysql
 - mysqli
 - intl
 - gd
 - xsl
 - ldap
 - mcrypt
 - apcu
 - xdebug

Also there are installed & enabled those modules to the `apache2`:

 - rewrite
 - shib2
 - ssl
 - rpaf
 - remoteip

### How to use this?

I'm using this docker build in an [docker compose](https://docs.docker.com/compose/) configuration. See the `docker-compose.yaml` file in the root of this repository.

