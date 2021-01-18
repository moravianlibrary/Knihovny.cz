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

    WAYF_FILE_URL="$PARAM_VUFIND_URL/wayf.txt"
    # Encode for URL parameter:
    WAYF_FILE_URL=$(echo "$WAYF_FILE_URL" | sed -e 's/%/%25/g' -e 's/ /%20/g' -e 's/!/%21/g' -e 's/"/%22/g' -e 's/#/%23/g' -e 's/\$/%24/g' -e 's/\&/%26/g' -e 's/'\''/%27/g' -e 's/(/%28/g' -e 's/)/%29/g' -e 's/\*/%2a/g' -e 's/+/%2b/g' -e 's/,/%2c/g' -e 's/-/%2d/g' -e 's/\./%2e/g' -e 's/\//%2f/g' -e 's/:/%3a/g' -e 's/;/%3b/g' -e 's//%3e/g' -e 's/?/%3f/g' -e 's/@/%40/g' -e 's/\[/%5b/g' -e 's/\\/%5c/g' -e 's/\]/%5d/g' -e 's/\^/%5e/g' -e 's/_/%5f/g' -e 's/`/%60/g' -e 's/{/%7b/g' -e 's/|/%7c/g' -e 's/}/%7d/g' -e 's/~/%7e/g')

    sed -i \
        -e "s#PARAM_VUFIND_HOST#${PARAM_VUFIND_HOST}#g" \
        -e "s#PARAM_VUFIND_SSL_PORT#${PARAM_VUFIND_SSL_PORT}#g" \
        -e "s#PARAM_VUFIND_URL#${PARAM_VUFIND_URL}#g" \
        -e "s#PARAM_VUFIND_ENTITY_ID#${PARAM_VUFIND_ENTITY_ID}#g" \
        -e "s#PARAM_SSL_DIR#${PARAM_SSL_DIR:-/etc/ssl/private}#g" \
        -e "s#PARAM_SHIB_KEY_OUT#${PARAM_SHIB_KEY_OUT:-shibboleth2-sp-key.pem}#g" \
        -e "s#PARAM_SHIB_CRT_OUT#${PARAM_SHIB_CRT_OUT:-shibboleth2-sp-cert.pem}#g" \
        -e "s#WAYF_FILE_URL#${WAYF_FILE_URL}#g" \
        "$SHIB_CONF_ABS_PATH" || return $?

    /usr/local/bin/generate_key.sh "$PARAM_SSL_DIR/$PARAM_SHIB_KEY_OUT" "$PARAM_SSL_DIR/$PARAM_SHIB_CRT_OUT" \
        "$PARAM_VUFIND_HOST" "$PARAM_SSL_VALIDITY_DAYS" || return $?

    service shibd stop &>/dev/null

    service shibd start || (perror "Failed starting shibboleth daemon, something may be wrong with the configuration ..." && return 4)
}

main "$@"
exit $?
