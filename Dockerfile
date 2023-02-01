FROM php:7.4-cli
# Set working dir.
ARG CONTAINER_APP_DIR=/usr/src/workerman/edusohu-ci-push
RUN mkdir -p ${CONTAINER_APP_DIR}
WORKDIR ${CONTAINER_APP_DIR}
    
# Change ubuntu source.
# Reference: https://github.com/laradock/laradock
ARG UBUNTU_SOURCE=tsinghua
ARG CHANGE_UBUNTU_SOURCE=true
COPY ./sources.sh /tmp/sources.sh
RUN if [ ${CHANGE_UBUNTU_SOURCE} = true ]; then \
    apt update && \
    apt-get install -y gnupg2 && \
    apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 3B4FE6ACC0B21F32 && \
    chmod +x /tmp/sources.sh && \
    /bin/bash -c /tmp/sources.sh && \
    rm -rf /tmp/sources.sh && \
    apt update \
;fi

# Install php ext.
RUN apt update && \
    apt-get remove -y libssl1.1 && \
    apt-get install libssl1.1 krb5-locales libkrb5support0=1.16-2ubuntu0.1 libkrb5-3 libgssapi-krb5-2 libcurl4 --allow-downgrades -y && \
    docker-php-ext-install sockets pcntl && \
    docker-php-ext-install pdo_mysql && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apt-get install libssl-dev -y

ARG INSTALL_LIB_EVENT=true
RUN if [ ${INSTALL_LIB_EVENT} = true ]; then \
    apt-get install libevent-dev -y && \
    apt-get install -y zip && \
    pecl install event && \
    echo extension=event.so > /usr/local/etc/php/conf.d/event.ini \
;fi

# Install composer and set mirror.
# Reference: https://pkg.phpcomposer.com/#how-to-install-composer
# Referencr: https://developer.aliyun.com/composer
ARG INSTALL_COMPOSER=true
ARG COMPOSER_INSTALLER=https://install.phpcomposer.com/installer
ARG COMPOSER_MIRROR=https://mirrors.aliyun.com/composer/
RUN if [ ${INSTALL_COMPOSER} = true ]; then \
    php -r "copy('${COMPOSER_INSTALLER}', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer && \
    composer config -g repo.packagist composer ${COMPOSER_MIRROR} \
;fi

ADD ./ ${CONTAINER_APP_DIR}
RUN composer install && cp .env.example .env

# Expose port
ARG CONTAINER_PORT=8787
EXPOSE ${CONTAINER_PORT}

CMD  [ "php", "start.php", "start" ]
#ENTRYPOINT  [ "php", "start.php", "start" ]
