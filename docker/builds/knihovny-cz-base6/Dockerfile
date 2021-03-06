FROM localhost/apache-shibboleth6

ARG PARAM_VUFIND_SRC=/var/www/knihovny-cz
ARG PARAM_VUFIND_PORT=443

WORKDIR /var/www/knihovny-cz

EXPOSE 80
EXPOSE ${PARAM_VUFIND_PORT}

# What was specified as argument on build time, pass as ENV ..
ENV PARAM_VUFIND_SRC=${PARAM_VUFIND_SRC}
ENV PARAM_VUFIND_PORT=${PARAM_VUFIND_PORT}
ENV PARAM_VUFIND_ROBOTS_TXT=${PARAM_VUFIND_ROBOTS_TXT}

ENV PARAM_VUFIND_CONFIG_DIR=${PARAM_VUFIND_CONFIG_DIR}

ENV PARAM_VUFIND_GOOGLE_API_KEY=${PARAM_VUFIND_GOOGLE_API_KEY}
ENV PARAM_VUFIND_CAPTCHA_SITE_KEY=${PARAM_VUFIND_CAPTCHA_SITE_KEY}
ENV PARAM_VUFIND_CAPTCHA_SECRET_KEY=${PARAM_VUFIND_CAPTCHA_SECRET_KEY}
ENV PARAM_VUFIND_MYSQL_URL=${PARAM_VUFIND_MYSQL_URL}
ENV PARAM_VUFIND_COMMIT="c321970b8f2862276eed71f65400a4a21531f73e"
ENV PARAM_PORTAL_PAGES_BRANCH=${PARAM_PORTAL_PAGES_BRANCH}

## Install base VuFind

RUN echo "${PARAM_VUFIND_COMMIT}"
# Take a vufind source at given state
RUN curl "https://github.com/vufind-org/vufind/archive/${PARAM_VUFIND_COMMIT}.zip" -L -o /tmp/vufind.zip

# Unzip the files
RUN unzip -q /tmp/vufind.zip -d /tmp

# Move them to target place
RUN cp -rl /tmp/vufind-${PARAM_VUFIND_COMMIT}/* /var/www/knihovny-cz/ && \
    mv /tmp/vufind-${PARAM_VUFIND_COMMIT}/.[!.]* /var/www/knihovny-cz/ && \
    rm -r /tmp/vufind-${PARAM_VUFIND_COMMIT} && \
    rm /tmp/vufind.zip

# Add local dependecies
COPY ./composer.local.json /var/www/knihovny-cz/

# We need to remove original VuFind composer.lock file to composer.local.json be taken into account
RUN rm /var/www/knihovny-cz/composer.lock

# Install composer dependencies
RUN composer install --no-interaction -q

# Copy script for content updating and run it on every start
COPY install-portal-content.sh /onstart/010-install-portal-content.sh

#TODO: sentry/sentry still missing

# Copy clear cache script
COPY clear-cache /usr/local/bin/

COPY ./knihovny-cz-foreground /usr/local/bin/

COPY phpunit.xml /var/www/knihovny-cz/

CMD ["knihovny-cz-foreground"]
