#!/usr/bin/env bash

perror() {
    echo "$@" >&2
}

main() {

    local SHIB_CONF_DIR=/etc/shibboleth
    local SHIB_CONF_TEMPL="shibboleth2.xml.templ"
    local SHIB_CONF="shibboleth2.xml"

    local SHIB_CONF_TEMPL_ABS_PATH="${SHIB_CONF_DIR}/${SHIB_CONF_TEMPL}"
    if test ! -f "$SHIB_CONF_TEMPL_ABS_PATH"; then
        perror "Shibboleth configuration could not be set! It's missing the template file '$SHIB_CONF_TEMPL_ABS_PATH'"
        return 2
    fi

    local SHIB_CONF_ABS_PATH="${SHIB_CONF_DIR}/${SHIB_CONF}"

    cp "$SHIB_CONF_TEMPL_ABS_PATH" "$SHIB_CONF_ABS_PATH" || (perror "Failed altering current configuration! Probably wrong permissions?" && return 3)

    if [ -z "$PARAM_VUFIND_HOST" ]; then
        PARAM_VUFIND_HOST="localhost"
    fi;

    if [ -z "$PARAM_VUFIND_SSL_PORT" ]; then
        PARAM_VUFIND_SSL_PORT="443"
    fi;

    if [ -z "$PARAM_VUFIND_URL" ]; then
        PARAM_VUFIND_URL="https://$PARAM_VUFIND_HOST"
        if [ "$PARAM_VUFIND_SSL_PORT" != "443" ]; then
            PARAM_VUFIND_URL="https://$PARAM_VUFIND_HOST:$PARAM_VUFIND_SSL_PORT"
        fi;
    fi;

    if [ -z "$PARAM_VUFIND_ENTITY_ID" ]; then
        PARAM_VUFIND_ENTITY_ID=$PARAM_VUFIND_URL
    fi;

    sed -i \
        -e "s#PARAM_VUFIND_HOST#${PARAM_VUFIND_HOST}#g" \
        -e "s#PARAM_VUFIND_SSL_PORT#${PARAM_VUFIND_SSL_PORT}#g" \
        -e "s#PARAM_VUFIND_URL#${PARAM_VUFIND_URL}#g" \
        -e "s#PARAM_VUFIND_ENTITY_ID#${PARAM_VUFIND_ENTITY_ID}#g" \
        -e "s#PARAM_SSL_DIR#${PARAM_SSL_DIR:-/etc/ssl/private}#g" \
        -e "s#PARAM_SHIB_KEY_OUT#${PARAM_SHIB_KEY_OUT:-shibboleth2-sp-key.pem}#g" \
        -e "s#PARAM_SHIB_CRT_OUT#${PARAM_SHIB_CRT_OUT:-shibboleth2-sp-cert.pem}#g" \
        "$SHIB_CONF_ABS_PATH" || return $?

    /usr/local/bin/generate_key.sh "$PARAM_SSL_DIR/$PARAM_SHIB_KEY_OUT" "$PARAM_SSL_DIR/$PARAM_SHIB_CRT_OUT" \
        "$PARAM_VUFIND_HOST" "$SSL_VALIDITY_DAYS" || return $?

    service shibd stop &>/dev/null
    
    service shibd start || (perror "Failed starting shibboleth daemon, something may be wrong with the configuration ..." && return 4)
}

main "$@"
exit $?
