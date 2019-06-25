All of your local configurations and customizations belong in this directory and its
subdirectories.  Rather than modifying core VuFind files, you should copy them here
and modify them within the local directory.  This will make upgrades easier.

The configuration of views/instances in Knihovny.cz portal is organized as described below:

1. For each configuration file there exists base config file in local/config/vufind/
2. There could be file [config_name].local.ini for local customzations - useful for development
3. The configuration file in the instance itself does inherit from the 2 file mentioned above and could add own specific configuration options


