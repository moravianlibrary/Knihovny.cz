#!/usr/bin/env bash

perror() {
    echo "$@" >&2
}

init_config_local() {

    CONFIG_LOCAL="${PARAM_VUFIND_CONFIG_ABS_DIR}/config/vufind/config.local.ini"

    PARAM_VUFIND_URL="https://$PARAM_VUFIND_HOST:$PARAM_VUFIND_SSL_PORT/"

    cp /tmp/config.local.template.ini "$CONFIG_LOCAL"
    sed -i \
        -e "s#PARAM_VUFIND_DEBUG#${PARAM_VUFIND_DEBUG:-false}#g" \
        -e "s#PARAM_VUFIND_CAPTCHA_SITE_KEY#${PARAM_VUFIND_CAPTCHA_SITE_KEY}#g" \
        -e "s#PARAM_VUFIND_CAPTCHA_SECRET_KEY#${PARAM_VUFIND_CAPTCHA_SECRET_KEY}#g" \
        -e "s#PARAM_VUFIND_URL#${PARAM_VUFIND_URL}#g" \
        -e "s#PARAM_VUFIND_SOLR_URL#${PARAM_VUFIND_SOLR_URL}#g" \
        -e "s#PARAM_VUFIND_SOLR_INDEX#${PARAM_VUFIND_SOLR_INDEX}#g" \
        -e "s#PARAM_VUFIND_MYSQL_URL#${PARAM_VUFIND_MYSQL_URL}#g" \
        -e "s#PARAM_VUFIND_GOOGLE_API_KEY#${PARAM_VUFIND_GOOGLE_API_KEY}#g" \
        -e "s#PARAM_VUFIND_REDIS_HOST#${PARAM_VUFIND_REDIS_HOST}#g" \
        -e "s#PARAM_VUFIND_REDIS_PASSWORD#${PARAM_VUFIND_REDIS_PASSWORD}#g" \
        "$CONFIG_LOCAL"
}

init_eds_config() {
    if [ -z "$PARAM_VUFIND_EDS_LOGIN" ]; then
        return 0
    fi;

    CONFIG_EDS="${PARAM_VUFIND_CONFIG_ABS_DIR}/config/vufind/EDS.local.ini"
    cp /tmp/EDS.local.template.ini "$CONFIG_EDS"
    sed -i \
        -e "s#PARAM_VUFIND_EDS_LOGIN#${PARAM_VUFIND_EDS_LOGIN}#g" \
        -e "s#PARAM_VUFIND_EDS_PASSWD#${PARAM_VUFIND_EDS_PASSWD}#g" \
        -e "s#PARAM_VUFIND_EDS_PROFILE#${PARAM_VUFIND_EDS_PROFILE}#g" \
        "$CONFIG_EDS"
}

init_search2_config() {
    CONFIG_SEARCH2="${PARAM_VUFIND_CONFIG_ABS_DIR}/config/vufind/Search2.local.ini"

    cp /tmp/Search2.local.template.ini "$CONFIG_SEARCH2"
    sed -i \
        -e "s#PARAM_VUFIND_SEARCH2_SOLR_URL#${PARAM_VUFIND_SEARCH2_SOLR_URL}#g" \
        -e "s#PARAM_VUFIND_SEARCH2_SOLR_INDEX#${PARAM_VUFIND_SEARCH2_SOLR_INDEX}#g" \
        "$CONFIG_SEARCH2"
}

init_content_config() {
    CONFIG_CONTENT="${PARAM_VUFIND_CONFIG_ABS_DIR}/config/vufind/content.local.ini"

    cp /tmp/content.local.template.ini "$CONFIG_CONTENT"
    sed -i \
        -e "s#PARAM_PORTAL_PAGES_BRANCH#${PARAM_PORTAL_PAGES_BRANCH}#g" \
        "$CONFIG_CONTENT"
}

init_config_local "$@"
init_eds_config "$@"
init_search2_config "$@"
init_content_config "$@"
exit $?
