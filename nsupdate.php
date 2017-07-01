#!/usr/bin/php
<?php
/** 
 * nsupdate.php
 * This script is used to update records on local dns server from data in MySQL ddns DB
 *
 * @author      Chester Pang<bo@bearpang.com> 
 * @version     1.1
 */  



/*
 * Read from stdin
 *
 * @return string $input    User's input
 */

function stdin(){  
    $fp = fopen('/dev/stdin', 'r');  
    $input = fgets($fp, 255);  
    fclose($fp);  
    $input = chop($input);  
    echo "-----------------------------------------\n"; //to devide input fileds    
    return $input;  
} 


/** 
 * Increse SOA Serial Number. 
 *
 * "yyyymmddxx" where xx indicated diff versions in a single day
 *
 * @version 1.0
 * @since 0.1
 *
 * @return string increased SOA Serial No.
 */

function SOA_Serial_INC(){
    $date = date("Ymd",time());
    if(!file_exists(SOA_FILE)){
        $new = fopen(SOA_FILE, "w");
        $new_str = $date."00";
        fwrite($new, $new_str);
        fclose($new);
        return $new_str;
    }

    $t = fopen(SOA_FILE,"r+");
    $SOA_Serial = fread($t, 10);
    if (substr($SOA_Serial, 0, 8) == $date)
        $SOA_Serial_updated = $SOA_Serial + 1;
    else
        $SOA_Serial_updated = $date."00";
    rewind($t);
    fwrite($t, $SOA_Serial_updated);
    fclose($t);
    return $SOA_Serial_updated;

}



/**
 * If first time running, run setup
 */
if(!file_exists("/etc/ddns/nsupdate-config.php") || (isset($argv[1]) ? $argv[1] == "reconfigure" : false)){
    echo "No configuation file detected, generating one...\n";
    if(!file_exists("/etc/ddns"))
        mkdir("/etc/ddns");

    echo "Please input your timezone. e.g. Asia/Shanghai or Australia/Sydney\n";
    echo "For more information about php timezone, please visit http://php.net/manual/en/timezones.php\n";
    $TIMEZONE = stdin();
    echo "Please specify your dns zone name, please refer to your bind configuration file.\n";
    $ZONE = stdin();
    echo "Please input your SOA record TTL. Press Enter to use the default value. (600)\n";
    $input = stdin();
    $TTL = $input ? $input : "600";
    echo "Please input your primary dns server for SOA record.\n";
    $dns = stdin();
    echo "Please input your email for SOA record. Use dot to replace @. e.g. name.example.com\n";
    $email = stdin();
    $SOA_PREFIX = $ZONE . " " . $TTL . " SOA " . $dns . " " . $email . " ";
    echo "Please input your Refresh, Retry, Expire, Default TTL values. Use single space to devide these values.\n";
    echo "Press Enter for defualt value. (900 60 604800 60)\n";
    $input = stdin();
    $SOA_SUFFIX = $input ? " " . $input : " 900 60 604800 60";
    echo "Please input your MySQL Database Host. Press Enter to use default host. (localhost)\n";
    $input = stdin();
    $DB_HOST = $input ? $input : "localhost";
    echo "Please input your MySQL Database username.\n";
    $DB_USER = stdin();
    echo "Please input your password.\n";
    $DB_PASSWD = stdin();
    echo "Please input your databse name used for this ddns script.\n";
    $DB_DBNAME = stdin();
    echo "Please input a path to the file to place ddns.php under your webroot\n";
    echo "Press Enter for defualt value. (/var/www/html/)\n";
    $input = stdin();
    $WEB_ROOT = $input ? $input : "/var/www/html/";
    echo "Please enter your ddns.php auth username and password.\n";
    $usr = stdin();
    echo "Password:";
    $passwd = stdin();

    echo "Starting to write configuration files...\n";
    $file1 = 
    "<?php
/** 
 * nsupdate-config.php
 * This is a automatically generated configuration file, used to define constant needed for nsupdate.php
 *
 * @see        https:/dev.bopa.ng/chester/php-ddns
 * @version    1.0 
 */  

//Constant definitions 
define(\"SOA_FILE\", \"/etc/ddns/SOA_Serial\"); //file used to save current SOA Serial number
define(\"TIMEZONE\", \"$TIMEZONE\");
define(\"ZONE\", \"$ZONE\");  //dns zone
define(\"SOA_PREFIX\", \"$SOA_PREFIX\"); //SOA record before serial
define(\"SOA_SUFFIX\", \"$SOA_SUFFIX\");


?>";
    $file2 = 
    "<?php
/** 
 * DB-config.php
 * This is a automatically generated configuration file, used to define MySQL connection details for nsupdate.php and ddns.php
 *
 * @see        https:/dev.bopa.ng/chester/php-ddns
 * @version    1.0 
 */  

define(\"DB_HOST\", \"$DB_HOST\");
define(\"DB_USER\", \"$DB_USER\");
define(\"DB_PASSWD\", \"$DB_PASSWD\");
define(\"DB_DBNAME\", \"$DB_DBNAME\");
?>";

    $handle1 = fopen("/etc/ddns/nsupdate-config.php", "w");
    $handle2 = fopen("/etc/ddns/DB-config.php", "w");    

    if(!fwrite($handle1, $file1) || !fwrite($handle2, $file2))
        die("Write config file failed. Please check permissions.\n"); 
    else
        echo "Successfully wrote configuration files.\n";
    fclose($handle1);
    fclose($handle2);

    echo "Initialising database structure.\n";
    $sqlfile = fopen("ddns.sql", "r");
    $sql = fread($sqlfile, filesize("ddns.sql"));
    $sql .= "INSERT INTO USER (USERNAME, PASSWD) VALUES('$usr', md5('$passwd'));";
    $db = new mysqli($DB_HOST, $DB_USER, $DB_PASSWD, $DB_DBNAME);
    $ret = $db -> multi_query($sql); 
    if($ret === false) { 
        die ($db -> error); 
    } 
    while (mysqli_more_results($db)) { 
        if (mysqli_next_result($db) === false) { 
            echo $db -> error; 
            echo "\r\n";
            die("Failed."); 
            break; 
        } 
    } 

    echo "Database initialised successfully.\n";


    if(!copy("ddns.php", $WEB_ROOT . "ddns.php")){
        die("Failed to copy ddns.php to " . $WEB_ROOT . "\nCheck Permission!\n");
    }

    echo "Configuration process complete.\n";
    echo ("Please run this script again manually or add it into crontab.\n");
    exit();
}



//Initialise
require("/etc/ddns/nsupdate-config.php");
require("/etc/ddns/DB-config.php");
date_default_timezone_set(TIMEZONE);
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_DBNAME);


//=============================================================
//
//=============================================================

$handle = fopen("/tmp/nsupdate.cmd", "w");
$str = "server 127.0.0.1\r\n";
$str .= "zone " . ZONE . "\r\n";
@fwrite($handle, $str);

//Fetch newly updated records
$stmt = $db -> prepare("SELECT SN, FQDN, TTL, TYPE, VALUE FROM RR WHERE SOA_Serial=0");
$stmt -> execute();
$stmt -> bind_result($SN, $FQDN, $TTL, $type, $value);
$stmt -> store_result();
$updated_SNs = array();
if($stmt -> num_rows != 0){     //if new records
    while($stmt -> fetch()){
        $str = "update delete $FQDN $type\r\n";
        $str .= "update add $FQDN $TTL IN $type $value\r\n";
        @fwrite($handle, $str);
        array_push($updated_SNs, $SN);
    }
    

    //update named SOA Serial Number
    $SOA_Serial_updated = SOA_Serial_INC();
    $str = "update delete " . ZONE . " SOA\r\n";
    $str .= "update add " . SOA_PREFIX . $SOA_Serial_updated . SOA_SUFFIX . "\r\n";
    @fwrite($handle, $str);

    //update SOA Serial No. in DB
    foreach($updated_SNs as $SN){
        $stmt -> close(); 
        $stmt = $db -> prepare("UPDATE RR SET SOA_Serial=? WHERE SN=?");
        $stmt -> bind_param("ii", $SOA_Serial_updated, $SN);
        $stmt -> execute();
    }


    $str="send \r\n";
    @fwrite($handle,$str);
    @fclose($handle);
    exec('nsupdate -v /tmp/nsupdate.cmd');
}

$stmt -> close();
$db -> close();
?>