<?php
require 'vendor/autoload.php';

$_hostname = "your_hostname"; // change this to your hostname
$_username = "your_username"; // your database user name
$_password = "your_password"; // database user's password
$_database = "your_database"; // any of your databases
$_smtpname = "your_smtp_name"; // eg name@gmail.com
$_smtppassword = "your_smtp_password";
$_smtphost = "your_smtp_hostname"; // e.g smtp.gmail.com
$_baseUrl = "http://your_url"; // your base url (if you'll want to run backup from web)
$mailFrom = "robot@example.com"; // type in any mail adress, like robot@yourdomain.com
$mailTo = "your_email"; // input your mail which will reveive backup status and links to download .zip files
$fileDirectories = array("your_dir", "your_dir2"); // enter directories that you want to backup e.g /var/www/mysite

$date = date("Y-m-d");
$dir = "your_path"; //name of the folder that this file is in (enter full path e.g /var/www/mysite
$folderName = "backup";
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
    global $dir, $progress, $folderName;
    
    $folderName = $dir;
    $dir = $dir . "/";
    
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
            $path = $dir . "$databases[$a].sql";
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
    $path = $dir . $databasesName;
    $command = "zip -j $path $dir/*.*"; // we are zipping all database backup files into one .zip file
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
    global $dir, $fileDirectories, $websitesName, $progress, $_baseUrl, $databasesName, $websitesName, $folderName;
    
    $progress .= "Zipping websites ";
    $path = $dir . $websitesName;
    $command = "zip -r $path "; // we are zipping all directories that were marked for backup into one .zip
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
    
    chmodFolder();

    $progress .= "<br />";
    $progress .= "Database backup: <a href='$_baseUrl/$folderName/" . "$databasesName' target='_blank'>download</a><br />";
    $progress .= "Website backup: <a href='$_baseUrl/$folderName/" . "$websitesName' target='_blank'>download</a><br /><br />";
    $progress .= "Have a great day!<br /> <br />";
    $progress .= date("Y-m-d H:i:s") . "<br />";
    
    echo $progress;
    
    sendMail();
}

function chmodFolder()
{
    global $dir, $databasesName, $websitesName;
    
    $commands = array("chown apache:apache " . $dir, "chown apache:apache " . $dir . $databasesName, "chmod 755 " . $dir . $databasesName, "chown apache:apache " . $dir . $websitesName, "chmod 755 " . $dir . $websitesName);
    for($a = 0; $a < sizeof($commands); $a++)
    {
        exec($commands[$a], $output, $return);
    }
}

function sendMail()
{
    global $progress, $mailFrom, $mailTo, $_smtpname, $_smtppassword, $_smtphost;

    $mail = new PHPMailer;
    $mail->isSMTP(true);/*Set mailer to use SMTP*/
    $mail->Host = $_smtphost;/*Specify main and backup SMTP servers*/
    $mail->Port = 465;
    $mail->SMTPAuth = true;/*Enable SMTP authentication*/
    $mail->Username = $_smtpname;/*SMTP username*/
    $mail->Password = $_smtppassword;/*SMTP password*/
    $mail->SMTPSecure = 'ssl';
    $mail->From = 'backup@teroute.com';
    $mail->FromName = 'Lamp-backuper';
    $mail->addAddress($mailTo);/*Add a recipient*/
    $mail->WordWrap = 70;/*DEFAULT = Set word wrap to 50 characters*/
    $mail->isHTML(true);/*Set email format to HTML (default = true)*/
    $mail->Subject = $subject;
    $mail->Body    = $progress;
    $mail->AltBody = $progress;
    if(!$mail->send()) 
    {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    } 
    else 
    {
        echo 'Message sent!';
    }
    
    
}

removePrevious();