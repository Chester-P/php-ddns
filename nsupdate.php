#!/usr/bin/php
<?php
/** 
* nsupdate.php
* This script is used to update records on local dns server from data in MySQL ddns DB
*
* @author      Chester Pang<bo@bearpang.com> 
* @version     1.0 
*/  

/**
* Constant definitions 
*/
define("SOA_FILE", "/etc/ddns/SOA_Serial"); //file used to save current SOA Serial number
define("TIMEZONE", "Australia/Sydney");
define("DB_HOST", "localhost");
define("DB_USER", "ddns");
define("DB_PASSWD", "3il7RwmgrmYa0l6R");
define("DB_DBNAME", "ddns");
define("ZONE", "bopa.ng");  //dns zone
define("SOA_PREFIX", "bopa.ng 600 SOA ns-au.bearpang.com root.bopa.ng "); //SOA record before serial
define("SOA_SUFFIX", " 900 60 604800 60");

//Initialise
date_default_timezone_set(TIMEZONE);
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_DBNAME);


/** 
* SOA_Serial_INC
* Increse SOA Serial Number. 
* "yyyymmddxx" where xx indicated diff versions in a single day
* @version 0.1
* @since 0.1
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