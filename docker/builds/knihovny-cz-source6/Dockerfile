FROM localhost/knihovny-cz-base6

ARG PARAM_VUFIND_BRANCH=
ARG PARAM_VUFIND_COMMIT_HASH=
ARG PARAM_VUFIND_SRC=

COPY ./knihovny-cz-install.sh /usr/local/bin/knihovny-cz-install.sh
COPY ./config.local.template.ini /tmp/
COPY ./EDS.local.template.ini /tmp/
COPY ./bootstrap-source.sh /onstart/004-source.sh
RUN /usr/local/bin/knihovny-cz-install.sh "${PARAM_VUFIND_BRANCH}" "${PARAM_VUFIND_COMMIT_HASH}"

ENV PARAM_VUFIND_HOST=${PARAM_VUFIND_HOST}
ENV PARAM_VUFIND_SSL_PORT=${PARAM_VUFIND_SSL_PORT}
