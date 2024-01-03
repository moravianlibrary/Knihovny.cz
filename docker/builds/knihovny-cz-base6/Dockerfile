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
ENV PARAM_VUFIND_COMMIT="c02ff376dc577d4a816d99510fadb086a5db8d90"
ENV PARAM_PORTAL_PAGES_BRANCH=${PARAM_PORTAL_PAGES_BRANCH}
ENV PARAM_VUFIND_ROBOTS_TXT=${PARAM_VUFIND_ROBOTS_TXT}
ENV PARAM_VUFIND_SUMMON_API_ID=${PARAM_VUFIND_SUMMON_API_ID}
ENV PARAM_VUFIND_SUMMON_API_KEY=${PARAM_VUFIND_SUMMON_API_KEY}

## Install base VuFind

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
COPY ./Gruntfile.local.js /var/www/knihovny-cz/

# We need to remove original VuFind composer.lock file to composer.local.json be taken into account
RUN rm /var/www/knihovny-cz/composer.lock

# Install composer dependencies
RUN composer config --no-plugins allow-plugins.php-http/discovery true
RUN composer install --no-interaction -q --ignore-platform-req=php
RUN composer update --no-interaction -q --ignore-platform-req=php

#TODO: sentry/sentry still missing

# Copy clear cache script
COPY clear-cache /usr/local/bin/

COPY ./knihovny-cz-foreground /usr/local/bin/

COPY phpunit.xml /var/www/knihovny-cz/

# Install npm and grunt
COPY package.json /var/www/knihovny-cz/
RUN npm install -g grunt-cli && npm install

# Copy node modules
RUN cp ./node_modules/autocomplete.js/autocomplete.js ./themes/bootstrap3/js/vendor/
RUN cp ./node_modules/jstree/dist/jstree.min.js ./themes/bootstrap3/js/vendor/jsTree/
RUN cp -r ./node_modules/jstree/dist/themes ./themes/bootstrap3/js/vendor/jsTree/themes
RUN cp ./node_modules/vanilla-cookieconsent/dist/cookieconsent.umd.js ./themes/bootstrap3/js/vendor/

CMD ["knihovny-cz-foreground"]
