FROM localhost/knihovny-cz-base6

ARG PARAM_VUFIND_BRANCH
ARG PARAM_VUFIND_SRC
# We need PARAM_VUFIND_COMMIT_HASH to enforce docker to run git clone when the branch is updated
ARG PARAM_VUFIND_COMMIT_HASH
ARG GITLAB_DEPLOY_USER
ARG GITLAB_DEPLOY_PASSWORD

ENV PARAM_VUFIND_HOST=${PARAM_VUFIND_HOST}
ENV PARAM_VUFIND_SSL_PORT=${PARAM_VUFIND_SSL_PORT}
ENV PARAM_CRONJOB_SECRET=${PARAM_CRONJOB_SECRET}
ENV PARAM_VUFIND_ROBOTS_TXT=${PARAM_VUFIND_ROBOTS_TXT}

COPY ./knihovny-cz-install.sh /usr/local/bin/knihovny-cz-install.sh
COPY ./config.local.template.ini /tmp/
COPY ./Search2.local.template.ini /tmp/
COPY ./content.local.template.ini /tmp/
COPY ./obalkyknih.local.template.ini /tmp/
COPY ./palmknihy.local.template.ini /tmp/
COPY ./bootstrap-source.sh /onstart/004-source.sh

# PARAM_VUFIND_COMMIT_HASH is not used in script itself, but passed to enforce
# docker to run git clone when the branch is updated
RUN /usr/local/bin/knihovny-cz-install.sh "${PARAM_VUFIND_BRANCH}" "${GITLAB_DEPLOY_USER}" \
  "${GITLAB_DEPLOY_PASSWORD}" "${PARAM_VUFIND_COMMIT_HASH}"

