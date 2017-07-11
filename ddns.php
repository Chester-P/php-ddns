<?php
/** 
 * ddns.php
 * This page is used to receive DDNS requests and record these requests into local MySQL DB
 * 
 *
 * @author      Chester Pang<bo@bearpang.com> 
 * @version     1.1
 */  



/**
 * Retrive user's real ip address
 *
 * @since 1.0
 *
 * @return string $ip    User's ip address
 */

function getClientIP()  
{  
    if (getenv("HTTP_CLIENT_IP"))  
        $ip = getenv("HTTP_CLIENT_IP");  
    else if(getenv("HTTP_X_FORWARDED_FOR"))  
        $ip = getenv("HTTP_X_FORWARDED_FOR");  
    else if(getenv("REMOTE_ADDR"))  
        $ip = getenv("REMOTE_ADDR");  
    else $ip = "127.0.0.1";  
    return $ip;  
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

if(!isset($_REQUEST["ip"]))
    $ip = getClientIP();
else
    $ip = $_REQUEST["ip"];


//=============================================================
//
//=============================================================


if($FQDN){  //paras test
    $db = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_DBNAME);
    $stmt = $db -> prepare("SELECT 1 FROM USER WHERE USERNAME=? AND PASSWD=?");
    $stmt -> bind_param("ss", $user, $pass);
    $stmt -> execute();
    $stmt -> store_result();
    if ($stmt -> num_rows == 1){
       //echo "login success";
       $stmt -> close();
       $stmt = $db -> prepare("SELECT VALUE, TYPE FROM RR WHERE FQDN=?");
       $stmt -> bind_param("s", $FQDN);
       $stmt -> execute(); 
       $stmt -> bind_result($old_ip, $old_type);
       $stmt -> fetch();
       if($old_ip){   //Record exists, Update
            if($old_ip != $ip || $old_type != $type){ //Record changed, update record value and type
                $stmt -> close();
                $stmt = $db -> prepare("INSERT INTO RR_LOG(USERNAME, FQDN, TTL, TYPE, VALUE, CREATE_TIME, SOA_Serial) SELECT USERNAME, FQDN, TTL, TYPE, VALUE, CREATE_TIME, SOA_Serial FROM RR WHERE FQDN = ?"); //write change log
                $stmt -> bind_param("s", $FQDN);
                $stmt -> execute();
                $stmt -> close();
                $stmt = $db -> prepare("UPDATE RR SET TYPE=?, VALUE=?, TTL=?, CREATE_TIME=CURRENT_TIMESTAMP(6), SOA_Serial=0 WHERE FQDN=?");
                $stmt -> bind_param("ssis", $type, $ip, $ttl, $FQDN);
                if($stmt -> execute())
                    echo "Successfully updated record.";
                else
                    echo "Update record failed!\n";
                    echo $stmt -> error;
            }
            else{               //Record value remians the same, update timestamp
                $stmt -> close();
                $stmt = $db -> prepare("UPDATE RR SET CREATE_TIME=CURRENT_TIMESTAMP(6) WHERE FQDN=? and TYPE=?");
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
            $stmt = $db -> prepare("INSERT INTO RR (USERNAME, FQDN, TYPE, VALUE, TTL, CREATE_TIME) VALUES(?, ?, ?, ?, ?, CURRENT_TIMESTAMP(6))");
            $stmt -> bind_param("ssssi", $user, $FQDN, $type, $ip, $ttl);
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