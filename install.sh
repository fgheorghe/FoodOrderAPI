#!/bin/sh
curl -s http://getcomposer.org/installer | php
php composer.phar create-project symfony/framework-standard-edition php-app/ "2.3.*"