FROM lock8/lock8-base:latest
MAINTAINER Yerco <yerco@hotmail.com>

# create a non-root user
RUN groupadd -g 999 appuser && \
    useradd -r -u 999 -g appuser appuser

# create workdir and assign permissions to non-root user
RUN set -xe && \
    mkdir /opt/push_server  && \
    chown -R appuser:appuser /opt/push_server

# zmq extension for php
RUN apt-get update && apt-get install -y zlib1g-dev libzmq-dev wget git lsof \
    && pecl install zmq-beta \
    && docker-php-ext-install zip

EXPOSE 5556
EXPOSE 8028

RUN mkdir -p /home/appuser/.composer && \
    chown -R appuser:appuser /home/appuser/.composer && \
    chmod +w -R /home/appuser/.composer

# http://zeromq.org/bindings:php
RUN echo 'extension=zmq.so' >> /usr/local/etc/php/conf.d/docker-php-ext-zmq.ini

# switch from root to appuser
USER appuser

# chown included otherwise copied as root
COPY --chown=appuser:appuser composer.phar /opt/push_server
RUN chmod +x /opt/push_server/composer.phar

# chown included otherwise copied as root
COPY --chown=appuser:appuser composer.phar  /opt/push_server/
RUN chmod +x /opt/push_server/composer.phar
# copy PHP code
#COPY composer.json /opt/push_server/
COPY --chown=appuser:appuser . /opt/push_server/

WORKDIR /opt/push_server

# Libraries used for websocketing
RUN ./composer.phar install

CMD ["php", "/opt/push_server/WebsocketServer/PushServer.php"]
