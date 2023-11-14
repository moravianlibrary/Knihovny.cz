FROM php:8.1-apache-buster
LABEL maintainer="Moravian Library <cpk-dev@mzk.cz>"
LABEL version="2.0"
LABEL description="Based on the 'php:8.1-apache-buster' build with appended modules to php & apache2 + productivity tools included (vim, mc, htop, ping, traceroute, dig, ssh, mysql-client, locate, git, curl, bash-completion)"

#
# See the docs for the php docker image:
# - https://hub.docker.com/_/php/
#

ARG PARAM_XDEBUG_ENABLED=false
ARG PARAM_AGGRESSIVE_OPCACHE=false

ARG TMP_DEV_PKGS=" \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libldap2-dev \
        libldb-dev \
        libpng-dev \
        libxslt1-dev \
        libzip-dev"

# Install all libraries needed + basic productivity & debug tools
RUN apt-get update && apt-get install -y \
        bash-completion \
        ca-certificates \
        coreutils \
        curl \
        default-mysql-client \
        dnsutils \
        git \
        gnupg \
        htop \
        iputils-ping \
        iputils-tracepath \
        libapache2-mod-rpaf \
        libapache2-mod-shib \
        locales \
        mc \
        mlocate \
        net-tools \
        python \
        ssh \
        vim \
        ${TMP_DEV_PKGS} \
    && apt-get clean \
    && apt-get autoremove -y

# Install NodeJS
RUN mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && NODE_MAJOR=20 \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install nodejs -y

# Set Czech locales
RUN sed -i -e 's/# cs_CZ.UTF-8 UTF-8/cs_CZ.UTF-8 UTF-8/' /etc/locale.gen && \
    locale-gen
ENV LC_ALL=cs_CZ.UTF-8
ENV LANG=cs_CZ.UTF-8
ENV LANGUAGE=cs_CZ.UTF-8

# Enable apache modules
RUN a2enmod \
        rewrite \
        shib \
        rpaf \
        remoteip \
        proxy \
        proxy_html \
        proxy_http \
        xml2enc

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions apcu gd intl ldap mysqli opcache pdo_mysql soap xdebug xsl zip parle

COPY ./conf/php/php.ini /usr/local/etc/php/php.ini

COPY ./bootstrap-php.sh /onstart/001-php.sh

# Clean up after building from sources
RUN apt-get purge -y \
        ${TMP_DEV_PKGS}

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Update mlocate's files database
RUN updatedb
