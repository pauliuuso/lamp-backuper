# lamp-backuper
LAMP (linux, apache, mysql, php) - server backup tool.

# Installation

Installation is very simple:
1. Download backup.php
2. Open it in your prefered text editor
3. Enter your details (mysql authentication, directories you need to backup, etc.)
4. Upload this script to your server
5. You may put in a directory accesible by web browser and run backup from you browser.
6. Or you may use crontab, example "0 0 * * * /usr/bin/php -f /your_directory/backup.php > /var/spool/cron/backup.log" - this will make backups each day at 00:00


1. Download and unzip lichat.
2. In your server, create a mysql database and database user for lichat.
3. Go to your lichat's root folder, open "server/config.php" and enter mysql details.
4. Upload lichat to your server.
5. Open install directory example: http://your_lichat_url/install.
6. Install lichat, after it, delete install folder from your server.
7. That's it, you can login with your administrative account.

