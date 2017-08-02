<?php
/** 
 * ddns.php
 * This page is used to receive DDNS requests and record 
 * these requests into local MySQL DB.
 * 
 *
 * @author      Chester Pang <bo@bearpang.com> 
 * @version     1.1
 */  



/**
 * Retrive user's real ip address
 *
 * @since 1.0
 *
 * @return string $value    User's ip address
 */

function getClientIP()  
{  
    if (getenv("HTTP_CLIENT_IP"))  
        $value = getenv("HTTP_CLIENT_IP");  
    else if(getenv("HTTP_X_FORWARDED_FOR"))  
        $value = getenv("HTTP_X_FORWARDED_FOR");  
    else if(getenv("REMOTE_ADDR"))  
        $value = getenv("REMOTE_ADDR");  
    else $value = "127.0.0.1";  
    return $value;  
}  



//Initialise
require_once("/etc/ddns/DB-config.php"); //require DB connection details

$user = $_REQUEST["user"];
$pass = md5($_REQUEST["pass"]);
$FQDN = $_REQUEST["FQDN"];

if(!isset($_REQUEST["ttl"]))
    $ttl = '60';
else
    $ttl = $_REQUEST["ttl"];

if(!isset($_REQUEST["type"]))
    $type = 'A';
else
    $type = $_REQUEST["type"];

if(!isset($_REQUEST["value"]))
    $value = getClientIP();
else
    $value = $_REQUEST["value"];


//=============================================================
//
//=============================================================

/**
 * To do list:
 *      multiple zone support
 *      zone-specific auth for diff accounts
 *      delete records support
 *      
 */


if($FQDN){  //paras test
    $db = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_DBNAME);
    $stmt = $db -> prepare("SELECT 1 FROM user WHERE username=? AND password=?");
    $stmt -> bind_param("ss", $user, $pass);
    $stmt -> execute();
    $stmt -> store_result();
    if ($stmt -> num_rows == 1){
       //echo "login success";
       $stmt -> close();
       $stmt = $db -> prepare("SELECT value, type FROM RR WHERE FQDN=?");
       $stmt -> bind_param("s", $FQDN);
       $stmt -> execute(); 
       $stmt -> bind_result($old_ip, $old_type);
       $stmt -> fetch();
       if($old_ip){   //Record exists, Update
            if($old_ip != $value || $old_type != $type){ //Record changed, update record value and type
                $stmt -> close();
                //write change log
                $stmt = $db -> prepare(
                    "INSERT INTO RR_LOG(username, FQDN, TTL, type, value, create_time, SOA_Serial) ".
                    "SELECT username, FQDN, TTL, type, value, create_time, SOA_Serial FROM RR WHERE FQDN = ?"); 
                $stmt -> bind_param("s", $FQDN);
                $stmt -> execute();
                $stmt -> close();
                $stmt = $db -> prepare
                    ("UPDATE RR SET type=?, value=?, TTL=?, create_time=CURRENT_TIMESTAMP(6), SOA_Serial=0 WHERE FQDN=?");
                $stmt -> bind_param("ssis", $type, $value, $ttl, $FQDN);
                if($stmt -> execute())
                    echo "Successfully updated record.";
                else
                    echo "Update record failed!\n";
                    echo $stmt -> error;
            }
            else{               //Record value remians the same, update timestamp
                $stmt -> close();
                $stmt = $db -> prepare("UPDATE RR SET create_time=CURRENT_TIMESTAMP(6) WHERE FQDN=? and type=?");
                $stmt -> bind_param("ss", $FQDN, $type);
                if($stmt -> execute())
                    echo "IP is not changed, successfully updated timestamp.";
                else
                    echo "Update timestamp failed!\n";
                    echo $stmt -> error;

            }
       }
       else{    //Records does not exist, Insert
            $stmt -> close();
            $stmt = $db -> prepare
                ("INSERT INTO RR (username, FQDN, type, value, TTL, create_time) VALUES(?, ?, ?, ?, ?, CURRENT_TIMESTAMP(6))");
            $stmt -> bind_param("ssssi", $user, $FQDN, $type, $value, $ttl);
            if($stmt -> execute()){
                echo "Successfully inserted record.";
            }
            else{
                echo "Insert record failed!";
                echo $stmt -> error;
            }


       }

    }
    else{
        echo "Invalid login credentials!";
    
    }
    $stmt -> close();
    $db -> close();
}
else{   //paras test fail
    echo "Invalid parameters!";
}

?>