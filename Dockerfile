#FROM ubuntu:latest
FROM public.ecr.aws/lts/ubuntu:latest

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update -y && apt-get upgrade -y && \
    apt-get install -y apache2 php curl p7zip

WORKDIR /var/www/html

# Download and verify Composer installer
RUN curl -sSL https://getcomposer.org/installer > composer-setup.php && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

# Install Composer
RUN php composer-setup.php

# Cleanup installer
RUN rm composer-setup.php
#RUN php -r "unlink('composer-setup.php');"

# Move Composer to global path (optional)
RUN mv /var/www/html/composer.phar /usr/bin/composer

# Install AWS SDK for PHP
#RUN php -d memory_limit=-1 composer.phar require aws/aws-sdk-php
RUN composer require aws/aws-sdk-php

COPY file/* /var/www/html/
COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

# Expose port 80 for Apache
EXPOSE 80

# Start Apache in the foreground

#CMD ["apache2", "-f"]

#EXPOSE 80
CMD ["apachectl", "-D", "FOREGROUND"]