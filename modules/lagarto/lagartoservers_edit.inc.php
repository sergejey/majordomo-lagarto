<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='lagartoservers';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
  //updating 'TITLE' (varchar, required)
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }

  //updating 'IP' (varchar)
   global $ip;
   $rec['IP']=$ip;

   if (!$rec['IP']) {
    $ok=0;
    $out['ERR_IP']=1;
   }

   global $port;
   $rec['PORT']=(int)$port;

   global $zmqport;
   $rec['ZMQPORT']=(int)$zmqport;


   if (!$rec['PORT']) {
    $ok=0;
    $out['ERR_PORT']=1;
   }

   global $update_period;
   $rec['UPDATE_PERIOD']=(int)$update_period;
   if ($update_period>0) {
    $rec['NEXT_UPDATE']=date('Y-m-d H:i:s');
   }


  }

  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update

    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record

     $this->readValues($rec['ID']);
     /*
     $total=8;
     for($i=0;$i<$total;$i++) {
      $prop=array();
      $prop['DEVICE_ID']=$rec['ID'];
      $prop['NUM']=$i;
      $prop['TYPE']=0;
      SQLInsert('lagartoendpoints', $prop);

      unset($prop['ID']);
      $prop['TYPE']=1;
      SQLInsert('lagartoendpoints', $prop);
     }
     */

    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  //options for 'TYPE' (select)
  $tmp=explode('|', DEF_TYPE_OPTIONS);
  foreach($tmp as $v) {
   if (preg_match('/(.+)=(.+)/', $v, $matches)) {
    $value=$matches[1];
    $title=$matches[2];
   } else {
    $value=$v;
    $title=$v;
   }
   $out['TYPE_OPTIONS'][]=array('VALUE'=>$value, 'TITLE'=>$title);
   $type_opt[$value]=$title;
  }
  for($i=0;$i<count($out['TYPE_OPTIONS']);$i++) {
   if ($out['TYPE_OPTIONS'][$i]['VALUE']==$rec['TYPE']) {
    $out['TYPE_OPTIONS'][$i]['SELECTED']=1;
    $out['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
    $rec['TYPE']=$out['TYPE_OPTIONS'][$i]['TITLE'];
   }
  }
  }

  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);

  if ($rec['ID']) {
   $properties=SQLSelect("SELECT * FROM lagartoendpoints WHERE SERVER_ID='".$rec['ID']."' ORDER BY ENDPOINT_ID");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($this->mode=='update' && $this->tab=='data') {

     global ${"linked_object".$properties[$i]['ID']};
     global ${"linked_property".$properties[$i]['ID']};
     global ${"linked_method".$properties[$i]['ID']};
     $properties[$i]['LINKED_OBJECT']=${"linked_object".$properties[$i]['ID']};
     $properties[$i]['LINKED_PROPERTY']=${"linked_property".$properties[$i]['ID']};
     $properties[$i]['LINKED_METHOD']=${"linked_method".$properties[$i]['ID']};


     SQLUpdate('lagartoendpoints', $properties[$i]);
     if ($properties[$i]['LINKED_OBJECT']) {
      addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
     }


     $this->readValues($rec['ID']);

    }
   }
   $out['PROPERTIES']=$properties;

  }



  if ($this->mode=='getdata') {
   $this->readValues($rec['ID']);
   $this->redirect("?view_mode=".$this->view_mode."&tab=".$this->tab."&id=".$rec['ID']);
  }

  global $result;
  $out['RESULT']=$result;
