#!/bin/bash
set -e

KEY="/secrets/gitlab-deploy-key"

if [ ! -f "/secrets/gitlab-deploy-key" ]; then
  echo "Missing $KEY"
  exit 1
fi

mkdir -p /git
cd /git

HOST="gitlab.mzk.cz"

# In case the file does not exist yet
mkdir -p ~/.ssh
touch ~/.ssh/known_hosts

# Add host into known_hosts if not present
if ! grep "$(ssh-keyscan $HOST 2>/dev/null)" ~/.ssh/known_hosts > /dev/null; then
    ssh-keyscan $HOST >> ~/.ssh/known_hosts
fi

mkdir -p /var/www/.ssh
cp ~/.ssh/known_hosts /var/www/.ssh/
chown -R www-data:www-data /var/www/.ssh
chown www-data "$KEY"

if test ! -e "portal-pages"; then
    git clone --depth 1 --no-single-branch "git@$HOST:knihovny.cz/portal-pages.git" -c core.sshCommand="ssh -i $KEY"
fi
cd portal-pages
git checkout $PARAM_PORTAL_PAGES_BRANCH

chown -R www-data:www-data /git/portal-pages

cp -r /git/portal-pages/data/* /var/www/knihovny-cz-extension/themes/KnihovnyCz/

