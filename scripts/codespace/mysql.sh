cd
VERSION=$(php -r 'echo phpversion();')
wget https://www.php.net/distributions/php-$VERSION.tar.gz
tar xf php-$VERSION.tar.gz 
cd php-$VERSION/ext/pdo_mysql
phpize
./configure
make
make test
make install
sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/' /opt/php/$VERSION/ini/php.ini

php -i | grep drivers