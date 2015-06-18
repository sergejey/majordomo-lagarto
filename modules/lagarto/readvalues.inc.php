<?php

 $record=SQLSelectOne("SELECT * FROM lagartoservers WHERE ID='".(int)$id."'");


 if ($endpoint_id) {
  $content=getURL('http://' . $record['IP'] . ':' . $record['PORT'] . '/values?id=' . $endpoint_id, 0);
 } else {
  if ($record['UPDATE_PERIOD']) {
    $record['NEXT_UPDATE']=date('Y-m-d H:i:s', time()+$record['UPDATE_PERIOD']);
    SQLUpdate('lagartoservers', $record);
  }
  $content=getURL('http://' . $record['IP'] . ':' . $record['PORT'] . '/values?', 0);
 }

 $data=json_decode($content, TRUE);

 if (is_array($data['lagarto']['status'])) {
  $properties=$data['lagarto']['status'];
  $total=count($properties);
  for($i=0;$i<$total;$i++) {

   $prop=SQLSelectOne("SELECT * FROM lagartoendpoints WHERE SERVER_ID='".$record['ID']."' AND ENDPOINT_ID='".$properties[$i]['id']."'");
   if (!$prop['ID']) {
    $prop=array();
    $prop['ENDPOINT_ID']=$properties[$i]['id'];
    $prop['SERVER_ID']=$record['ID'];
    $prop['ID']=SQLInsert('lagartoendpoints', $prop);
   }

   $old_value=$prop['CURRENT_VALUE_STRING'];

   $prop['TITLE']=$properties[$i]['name'];
   $prop['TYPE_STRING']=$properties[$i]['type'];
   $prop['DIRECTION_STRING']=$properties[$i]['direction'];
   $prop['CURRENT_VALUE_STRING']=$properties[$i]['value'];
   $prop['UPDATED']=date('Y-m-d H:i:s', strtotime($properties[$i]['timestamp']));

   if ($prop['TYPE_STRING']=='bin') {
    if (strtolower($prop['CURRENT_VALUE_STRING'])=='off') {
     $prop['CURRENT_VALUE_STRING']=0;
    } else {
     $prop['CURRENT_VALUE_STRING']=1;
    }
   }

   SQLUpdate('lagartoendpoints', $prop);

    if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
      if ($old_value!=$prop['CURRENT_VALUE_STRING'] || $prop['CURRENT_VALUE_STRING']!=gg($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'])) {
        setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['CURRENT_VALUE_STRING'], array($this->name=>'0'));
      }
    }

    if ($prop['LINKED_OBJECT'] && $prop['LINKED_METHOD'] && ($old_value!=$prop['CURRENT_VALUE_STRING'])) {
      $params=array();
      $params['VALUE']=$prop['CURRENT_VALUE_STRING'];
      $params['value']=$params['VALUE'];
      $params['port']=$i;
      callMethod($prop['LINKED_OBJECT'].'.'.$prop['LINKED_METHOD'], $params);
    }

   
  }
 }

