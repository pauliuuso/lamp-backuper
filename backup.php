<?php

$_hostname = "your_hostname"; // change this to your hostname
$_username = "your_username"; // your database user name
$_password = "your_password"; // database user's password
$_database = "your_database"; // any of your databases
$_baseUrl = "http://your_url"; // your base url (if you'll want to run backup from web)
$mailFrom = "robot@example.com"; // type in any mail adress, like robot@yourdomain.com
$mailTo = "your_email"; // input your mail which will reveive backup status and links to download .zip files
$fileDirectories = array("your_dir", "your_dir2"); // enter directories that you want to backup e.g /var/www/mysite

$date = date("Y-m-d");
$dir = "backup";
$databasesName = "databases-$date.zip"; // name for database .zip file
$websitesName = "websites-$date.zip"; // name for websites .zip file
$progress = "";
$databases = array();
$connection = new mysqli($_hostname, $_username, $_password, $_database);
$databaseUrl = "";
$websiteUrl = "";

set_time_limit(0);
date_default_timezone_set('Europe/Vilnius');

function removePrevious()
{
    global $dir, $progress;
    
    if(file_exists($dir)) // if there is a folder with backup, we delete it first
    {
        $progress .= "Removing previous ";
        $command = "rm -rf $dir";
        exec($command, $output, $return);
        if(!$return)
        {
            $progress .= "- <span style='color: green;'>success!</span><br />";
        }
        else
        {
            $progress .= "- <span style='color: red;'>fail!</span> Error: " . shell_exec($command) . "<br />";
        }
    }
    
    if(!file_exists($dir)) // then we create a new one
    {
        $mask=umask(0);
        mkdir($dir, 0744);
        umask($mask);
    }
    
    getDatabases();
    
}

function getDatabases()
{
    global $databases, $connection;
    
    $sql = "SHOW DATABASES";
    $statement = mysqli_prepare($connection, $sql);
    $statement->execute();
    $statement->bind_result($database);
    
    while($statement->fetch()) // here we create an array of all databases
    {
        $databases[] = $database;
    }
    
    backupDatabases();
}

function backupDatabases()
{
    global $databases, $dir, $progress;
    
    for($a = 0; $a < sizeof($databases); $a++)
    {
        if($databases[$a] != "information_schema" && $databases[$a] != "performance_schema")
        {
            $path = getcwd() . "/" . $dir . "/$databases[$a].sql";
            $command = "mysqldump --user=root --password=freedom1000 --host=localhost $databases[$a] > $path"; // we are backuping each database, by calling mysqldump shell command
            $progress .= "Backuping $databases[$a] ";
            exec($command, $output, $return);
            if(!$return)
            {
                $progress .= "- <span style='color: green;'>success!</span><br />";
            }
            else
            {
                $progress .= "- <span style='color: red;'>fail!</span> Error: " . shell_exec($command) . "<br />";
            }
        }
    }
    
    zipDatabases();
    
}

function zipDatabases()
{
    global $dir, $databasesName, $progress;
    
    echo "Zipping databases ";
    $progress .= "Zipping databases ";
    $command = "zip -j $dir/$databasesName $dir/*.*"; // we are zipping all database backup files into one .zip file
    exec($command, $output, $return);
    if(!$return)
    {
        $progress .= "- <span style='color: green;'>success!</span><br />";
    }
    else
    {
        $progress .= "- <span style='color: red;'>fail!</span> Error: " . shell_exec($command) . "<br />";
    }
    zipWebpages();
}

function zipWebpages()
{
    global $dir, $fileDirectories, $websitesName, $progress, $_baseUrl, $databasesName, $websitesName;
    
    $progress .= "Zipping websites ";
    $command = "zip -r $dir/$websitesName "; // we are zipping all directories that were marked for backup into one .zip
    for($a = 0; $a < sizeof($fileDirectories); $a++)
    {
        $command .= $fileDirectories[$a] . " ";
    }
    exec($command, $output, $return);
    if(!$return)
    {
        $progress .= "- <span style='color: green;'>success!</span><br />";
    }
    else
    {
        $progress .= "- <span style='color: red;'>fail!</span> Error: " . shell_exec($command) . "<br />";
    }

    $progress .= "<br />";
    $progress .= "Database backup: <a href='$_baseUrl/$dir/" . "$databasesName' target='_blank'>download</a><br />";
    $progress .= "Website backup: <a href='$_baseUrl/$dir/" . "$websitesName' target='_blank'>download</a><br /><br />";
    $progress .= "Have a great day!<br /> <br />";
    $progress .= date("Y-m-d H:i:s") . "<br />";
    
    echo $progress;
    
    sendMail();
}

function sendMail()
{
    global $progress, $mailFrom, $mailTo;
    $subject = "LAMP backup succesful!";
    $headers = "From: $mailFrom\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n";
    mail($mailTo, $subject, $progress, $headers); // sending mail with backup status, and download links
}

removePrevious();