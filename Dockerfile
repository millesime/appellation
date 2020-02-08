FROM composer:latest
COPY . /usr/src/appellation
WORKDIR /usr/src/appellation
ENTRYPOINT ["php", "-d", "phar.readonly=Off", "./bin/appellation"]