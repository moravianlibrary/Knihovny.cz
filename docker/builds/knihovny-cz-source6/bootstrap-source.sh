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
        -e "s#PARAM_VUFIND_INFO_KNIHOVNY#${PARAM_VUFIND_INFO_KNIHOVNY}#g" \
        "$CONFIG_LOCAL"
}

init_eds_config() {
    CONFIG_EDS="${PARAM_VUFIND_CONFIG_ABS_DIR}/config/vufind/EDS.ini"
    if [ -z "$PARAM_VUFIND_EDS_LOGIN" ]; then
        return 0
    fi;

    # for compability with older versions before bug 990
    sed -i \
        -e "s#PARAM_VUFIND_EDS_LOGIN#${PARAM_VUFIND_EDS_LOGIN}#g" \
        -e "s#PARAM_VUFIND_EDS_PASSWD#${PARAM_VUFIND_EDS_PASSWD}#g" \
        -e "s#PARAM_VUFIND_EDS_PROFILE#${PARAM_VUFIND_EDS_PROFILE}#g" \
        "$CONFIG_EDS"

    # bug 990 - login configuration moved from EDS.ini to EDS.local.ini
    CONFIG_LOCAL_EDS="${PARAM_VUFIND_CONFIG_ABS_DIR}/config/vufind/EDS.local.ini"
    if [ ! -d "$CONFIG_LOCAL_EDS" ]; then
        cp /tmp/EDS.local.template.ini "$CONFIG_LOCAL_EDS"
        sed -i \
            -e "s#PARAM_VUFIND_EDS_LOGIN#${PARAM_VUFIND_EDS_LOGIN}#g" \
            -e "s#PARAM_VUFIND_EDS_PASSWD#${PARAM_VUFIND_EDS_PASSWD}#g" \
            -e "s#PARAM_VUFIND_EDS_PROFILE#${PARAM_VUFIND_EDS_PROFILE}#g" \
            "$CONFIG_LOCAL_EDS"
    fi;
}

init_config_local "$@"
init_eds_config "$@"
exit $?
