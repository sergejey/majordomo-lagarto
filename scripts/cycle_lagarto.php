<?php

chdir(dirname(__FILE__).'/../');

include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");

set_time_limit(0);

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME); 
 
include_once("./load_settings.php");
include_once(DIR_MODULES."control_modules/control_modules.class.php");

$ctl = new control_modules();

   include_once(DIR_MODULES.'lagarto/lagarto.class.php');
   $lagarto=new lagarto();

   $device=SQLSelectOne("SELECT ID FROM lagartoservers WHERE 1");
   if (!$device['ID']) {
    $db->Disconnect();
    echo "No devices found. Exit.";
    exit;
   }

echo date("H:i:s") . " running " . basename(__FILE__) . "\n";

if (class_exists('ZMQSocket')) {
//ZMQ PHP-library available
 $connected=array();
 $zmq_sockets=array();

 $connected_total=0;
 $servers=SQLSelect("SELECT * FROM lagartoservers");
 $total=count($servers);
 for($i=0;$i<$total;$i++) {
  /*
  if ($servers[$i]['IP']=='localhost' || $servers[$i]['IP']=='127.0.0.1') {
   continue;
  }
  */

  if (!$servers[$i]['ZMQPORT']) {
   continue;
  }

  echo "ZMQ server ".$servers[$i]['IP'].":".$servers[$i]['ZMQPORT']."\n";
  $zmq_socket = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_SUB);
  if ($zmq_socket->connect("tcp://".$servers[$i]['IP'].":".$servers[$i]['ZMQPORT'])) {
   echo "Connected\n";
   $zmq_socket->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, '');
   $connected[$i]=$servers[$i]['ID'];
   $connected_total++;
   $zmq_sockets[$i]=&$zmq_socket;
  } else {
   $zmq_sockets[$i]=0;
   $connected[$i]=0;
   echo "Could not connect\n";
  }
 }

 if ($connected_total>0) {
  while (true) {
    $total=count($servers);
    for($i=0;$i<$total;$i++) {
     if (!$connected[$i]) {
      continue;
     }
     $data = $zmq_sockets[$i]->recv(ZMQ::MODE_NOBLOCK);
     if ($data) {
      echo date("H:i:s") . " Received: $data\n";
      $lagarto->readValues($connected[$i], '', $data);
     }
    }

    if (file_exists('./reboot') || IsSet($_GET['onetime'])) 
    {
       $db->Disconnect();
       exit;
    }

  }
 }

}

// no ZMQ PHP-library available
while(1) 
{
   setGlobal((str_replace('.php', '', basename(__FILE__))).'Run', time(), 1);
   $lagarto->updateDevices(); 
   if (file_exists('./reboot') || IsSet($_GET['onetime'])) 
   {
      $db->Disconnect();
      exit;
   }

   sleep(1);
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));

