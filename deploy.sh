

ssh root@104.131.226.162 'ssh-agent bash -c "ssh-add ~/pks/dronweb.openssh; git -C /var/www/dron/dronweb fetch --all; git -C /var/www/dron/dronweb checkout --force origin/master"; composer self-update; composer install --working-dir /var/www/dron/dronweb'


