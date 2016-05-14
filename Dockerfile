FROM bileto/php-micro-service

RUN pecl install mongo \
  && echo "extension=mongo.so" > /etc/php5/mods-available/mongo.ini \
  && php5enmod mongo \
  && sed -i 's/variables_order = .*/variables_order = "EGPCS"/' /etc/php5/cli/php.ini \
  && sed -i 's/safe_mode_allowed_env_vars = .*/safe_mode_allowed_env_vars = ""/' /etc/php5/cli/php.ini \
  && sed -i 's/;date\.timezone =.*/date.timezone = "UTC"/' /etc/php5/cli/php.ini \
  && rm -rf /tmp/*


ADD src /srv/src
ADD config.neon /srv/config.neon
ADD composer.json /srv/composer.json
ADD composer.lock /srv/composer.lock

RUN  php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/bin --filename=composer \
  && cd /srv/ && composer install --no-interaction

WORKDIR /srv/src
EXPOSE 9000
ENTRYPOINT php run.php
