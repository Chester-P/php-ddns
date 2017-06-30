<?php
/** 
* ddns.php
* This page is used to record clients' IP addr and receive DDNS requests
* 
*
* @author      Chester Pang<bo@bearpang.com> 
* @version     0.1 
*/  


//Initialise variables
$user = $_REQUEST["user"];
$pass = $_REQUEST["pass"];
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
    $ip = $_SERVER["REMOTE_ADDR"];
else
    $ip = $_REQUEST["ip"];

if($FQDN){  //paras test
    $db = new mysqli("localhost","ddns","3il7RwmgrmYa0l6R","ddns");
    $stmt = $db -> prepare("SELECT 1 FROM USER WHERE USERNAME=? AND PASSWD=?");
    $stmt -> bind_param("ss", $user, $pass);
    $stmt -> execute();
    $stmt -> store_result();
    if ($stmt -> num_rows == 1){
       //echo "login success";
       $stmt -> close();
       $stmt = $db -> prepare("SELECT VALUE FROM RR WHERE FQDN=? and TYPE=?");
       $stmt -> bind_param("ss", $FQDN, $type);
       $stmt -> execute(); 
       $stmt -> bind_result($old_ip);
       $stmt -> fetch();
       if($old_ip){   //Record exists, Update
            if($old_ip != $ip){ //Record changed, write log
                $stmt -> close();
                $stmt = $db -> prepare("INSERT INTO RR_LOG(USERNAME, FQDN, TTL, TYPE, VALUE, CREATE_TIME) SELECT USERNAME, FQDN, TTL, TYPE, VALUE, CREATE_TIME FROM RR WHERE FQDN = ? and TYPE = ?");
                $stmt -> bind_param("ss", $FQDN, $type);
                $stmt -> execute();
                $stmt -> close();
                $stmt = $db -> prepare("UPDATE RR SET VALUE=?, TTL=?, CREATE_TIME=CURRENT_TIMESTAMP(6), SOA_Serial=0 WHERE FQDN=? and TYPE=?");
                $stmt -> bind_param("siss", $ip, $ttl, $FQDN, $type);
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
            $stmt = $db -> prepare("INSERT INTO RR (USERNAME, FQDN, TYPE, VALUE, TTL) VALUES(?, ?, ?, ?, ?)");
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