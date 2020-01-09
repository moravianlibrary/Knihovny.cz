## Knihovny.cz Docker configuration

This is configuration for running Knihovny.cz application (based on [vufind](https://github.com/vufind-org/vufind)) application using a Docker container.

It is based on Debian 10 "buster", with php7.4 taken from [here](https://hub.docker.com/_/php/). Concrete image is called `php7.4-apache-buster`.

### How does it work?

It is divided into three minor Docker containers, where one inherits from the other. You can see them in the `builds` directory.

On the top is the so called `knihovny-cz` container, which stands for `Knihovny.cz Centrální portál knihoven` alias `Knihovny.cz The Czechian Central Library Portal` to let everyone know about the origin of this project. That is https://www.knihovny.cz/. That container serves as an entrypoint to the application.

Under the `knihovny-cz` container is `apache-shibboleth` container, which takes care of properly configuring the [Apache2 httpd](https://httpd.apache.org/) service & the SAML [Shibboleth Service Provider](https://www.shibboleth.net/products/service-provider/), which is an identity management service.

Under the `apache-shibboleth` is `php-extensions` container, which inherits from `php7.4-apache-buster` container & installs all VuFind and Knihovny.cz prerequisities (according to the [Knihovny.cz](https://github.com/moravianlibrary/Knihovny.cz/)). This container also takes care of preparing the power user for productive environment with:

 - bash-completion
 - curl
 - dnsutils
 - git
 - htop
 - iputils-ping
 - iputils-tracepath
 - mc
 - mlocate
 - mysql-client
 - net-tools
 - ssh
 - vim
 

