#!/bin/bash

# Fix user rights
sudo usermod -a -G apache ec2-user
sudo chown -R ec2-user:apache /var/www
sudo chmod 2775 /var/www
find /var/www -type d -exec sudo chmod 2775 {} \;
find /var/www -type f -exec sudo chmod 0664 {} \;
sudo chmod 777 /var/www/html/resources/localstorage.db
sudo ln -sf /usr/share/zoneinfo/Europe/Berlin /etc/localtime
sudo systemctl restart php-fpm
sudo crontab -r -u root
sudo crontab -r -u ec2-user
echo "*/10 * * * * php /var/www/html/cronjobs/check_heartbeat.php >/dev/null 2>&1"  | crontab -
crontab -l | { cat; echo "0 4 * * * php /var/www/html/cronjobs/renew_subscription.php >/dev/null 2>&1"; } | crontab -
crontab -l | { cat; echo "*/10 * * * * php /var/www/html/cronjobs/check_service_status.php >/dev/null 2>&1"; } | crontab -

