# lamp-backuper
LAMP (linux, apache, mysql, php) - server backup tool.

# Installation

1. Download backup.php
2. Open it in your prefered text editor
3. Enter your details (mysql authentication, directories you need to backup, etc.)
4. Upload this script to your server
5. You may put in a directory accesible by web browser and run backup from you browser.
6. Or you may use crontab, example "0 0 * * * /usr/bin/php -f /your_directory/backup.php > /var/spool/cron/backup.log" - this will make backups each day at 00:00
