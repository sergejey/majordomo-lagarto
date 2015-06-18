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

while(1) 
{
   setGlobal((str_replace('.php', '', basename(__FILE__))).'Run', time(), 1);

   // check all 1wire devices
   $lagarto->updateDevices(); 
  
   if (file_exists('./reboot') || $_GET['onetime']) 
   {
      $db->Disconnect();
      exit;
   }

   sleep(1);
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));

