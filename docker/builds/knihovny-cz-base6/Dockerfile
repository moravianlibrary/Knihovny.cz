FROM localhost/apache-shibboleth

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
ENV PARAM_VUFIND_COMMIT="80021520ac5e51a4039f42f67ddeb1564b5e62ce"

## Install base VuFind

RUN echo "${PARAM_VUFIND_COMMIT}"
# Take a vufind source at given state
RUN curl "https://github.com/vufind-org/vufind/archive/${PARAM_VUFIND_COMMIT}.zip" -L -o /tmp/vufind.zip

# Unzip the files
RUN unzip -q /tmp/vufind.zip -d /tmp

# Move them to target place
RUN mv /tmp/vufind-${PARAM_VUFIND_COMMIT}/* /var/www/knihovny-cz/ && \
    mv /tmp/vufind-${PARAM_VUFIND_COMMIT}/.[!.]* /var/www/knihovny-cz/ && \
    rmdir /tmp/vufind-${PARAM_VUFIND_COMMIT} && \
    rm /tmp/vufind.zip

# Install composer dependencies
RUN composer install --no-interaction --no-scripts -q

# TODO
#RUN phing installswaggerui

#TODO: sentry/sentry still missing

COPY ./knihovny-cz-foreground /usr/local/bin/

CMD ["knihovny-cz-foreground"]
