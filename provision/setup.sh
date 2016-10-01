#!/usr/bin/env bash

# install xdebug
if php -i | grep xdebug > /dev/null; then
    echo 'xdebug is already installed'
else
    echo 'installing xdebug'

    sudo apt-get -y update --fix-missing
    sudo apt-get -y install php5-xdebug

    sudo touch /etc/php5/apache2/conf.d/20-xdebug.ini

    echo 'echo "xdebug.remote_enable=1" >> /etc/php5/apache2/conf.d/20-xdebug.ini' | sudo -s
    echo 'echo "xdebug.remote_connect_back=1" >> /etc/php5/apache2/conf.d/20-xdebug.ini' | sudo -s
    echo 'echo "xdebug.remote_port=9000" >> /etc/php5/apache2/conf.d/20-xdebug.ini' | sudo -s

fi;

# adjust some values for development
echo "adjust php development environment"
echo 'echo "display_errors = On" >> /etc/php5/apache2/conf.d/user.ini' | sudo -s
echo 'echo "error_reporting = E_ALL" >> /etc/php5/apache2/conf.d/user.ini' | sudo -s
echo 'echo "upload_max_filesize = 100M" >> /etc/php5/apache2/conf.d/user.ini' | sudo -s
echo 'echo "post_max_size = 100M" >> /etc/php5/apache2/conf.d/user.ini' | sudo -s
echo 'echo "max_execution_time=240" >> /etc/php5/apache2/conf.d/user.ini' | sudo -s
echo 'echo "xdebug.max_nesting_level" = 400 >> /etc/php5/apache2/conf.d/user.ini' | sudo -s

# php version fixes
echo "adjust php version fixes"
echo 'echo "always_populate_raw_post_data = -1" >> /etc/php5/apache2/conf.d/user.ini' | sudo -s

sudo service apache2 restart

