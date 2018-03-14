#!/bin/bash
mkdir deps
cd deps
curl -sS https://getcomposer.org/installer | php
php composer.phar require true/punycode:~2.0
