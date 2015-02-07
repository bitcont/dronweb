#ssh root@104.131.226.162
#DIR="$( cd "$( dirname "$0" )" && pwd )"

#git -C /var/www/dron/dronweb fetch --all
#git -C /var/www/dron/dronweb checkout --force origin/master

#composer self-update
#composer install --working-dir /var/www/dron/dronweb



ssh root@104.131.226.162 'git -C /var/www/dron/dronweb fetch --all; git -C /var/www/dron/dronweb checkout --force origin/master; composer self-update; composer install --working-dir /var/www/dron/dronweb'
