[![Maintainability](https://api.codeclimate.com/v1/badges/ebbd0826eedd428feae1/maintainability)](https://codeclimate.com/github/moravianlibrary/Knihovny.cz/maintainability)

# Knihovny.cz

Centrální portál knihoven / Central library portal

## Introduction

This software is a source code for the Knihovny.cz portal - discovery portal of Czech libraries, developed by the Moravian Library in Brno. It is based on VuFind open source discovery environment. To learn more about the portal, visit https://www.knihovny.cz. To learn more about VuFind, visit http://vufind.org.

## How to run / install ?
 
1. First you'll need to have [the docker](https://docs.docker.com/engine/installation/) installed (Click on the `Docker CE` on the left & pick your OS). 
2. Second, your user has to be in the `docker` group, unless you plan to run all docker related commands in privileged mode.
 
Once you've obtained docker, get docker-compose (using pip is probably the easiest method):
```bash
pip install docker-compose
```
 
Then you'll need to get sources:
```bash
git clone https://github.com/moravianlibrary/Knihovny.cz.git
```

Make some setup:
```bash
# Add the hostname beta.knihovny.cz to your /etc/hosts
echo "127.0.0.1        beta.knihovny.cz" | sudo tee -a /etc/hosts
 
cd Knihovny.cz 

# Go and configure your CPK-docker-compose
cp local.env{.example,}
vim local.env
 
# Go and configure your application
cp local/knihovny.cz/config/vufind/config.local.ini.example local/knihovny.cz/config/vufind
vim local/knihovny.cz/config/vufin/config.local.ini
```

Build docker images and run application:
```bash
bin/run.sh
```

Visit https://beta.knihovny.cz/ in your browser

For more options for building and running the appication see:
```bash
bin/run.sh -h
```
