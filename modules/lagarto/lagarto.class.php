<?php
/**
* lagarto 
*
* lagarto
*
* @package project
* @author Serge J. <jey@tut.by>
* @copyright http://www.atmatic.eu/ (c)
* @version 0.1 (wizard, 12:04:34 [Apr 09, 2015])
*/
//
//
class lagarto extends module {
/**
* lagarto
*
* Module class constructor
*
* @access private
*/
function lagarto() {
  $this->name="lagarto";
  $this->title="Lagarto (panStamp)";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  if (IsSet($this->device_id)) {
   $out['IS_SET_DEVICE_ID']=1;
  }
  if ($this->single_rec) {
   $out['SINGLE_REC']=1;
  }
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }


 if ($this->data_source=='lagartoservers' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_lagartoservers') {
   $this->search_lagartoservers($out);
  }
  if ($this->view_mode=='edit_lagartoservers') {
   $this->edit_lagartoservers($out, $this->id);
  }
  if ($this->view_mode=='delete_lagartoservers') {
   $this->delete_lagartoservers($this->id);
   $this->redirect("?data_source=lagartoservers");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='lagartoendpoints') {
  if ($this->view_mode=='' || $this->view_mode=='search_lagartoendpoints') {
   $this->search_lagartoendpoints($out);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 //$this->admin($out);
 $device=$_GET['device'];
 $command=$_GET['command'];

 if ($device && $command) {

  if ($this->sendCommand($device, $command)) {
   echo "OK";
  } else {
   echo "Error";
  }
 }

}
/**
* lagartoservers search
*
* @access public
*/
 function search_lagartoservers(&$out) {
  require(DIR_MODULES.$this->name.'/lagartoservers_search.inc.php');
 }

 function readValues($id, $endpoint_id='', $content='') {
  require(DIR_MODULES.$this->name.'/readvalues.inc.php');
 }



/**
* lagartoservers edit/add
*
* @access public
*/
 function edit_lagartoservers(&$out, $id) {
  require(DIR_MODULES.$this->name.'/lagartoservers_edit.inc.php');
 }

/**
* Title
*
* Description
*
* @access public
*/
 function refreshDevice($id) {
  $rec=SQLSelectOne("SELECT * FROM lagartoservers WHERE ID='".$id."'");
  if (!$rec['ID']) {
   return;
  }
  $this->readValues($rec['ID']);
 }

/**
* lagartoservers delete record
*
* @access public
*/
 function delete_lagartoservers($id) {
  $rec=SQLSelectOne("SELECT * FROM lagartoservers WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM lagartoendpoints WHERE SERVER_ID='".$rec['ID']."'");
  SQLExec("DELETE FROM lagartoservers WHERE ID='".$rec['ID']."'");
  
 }

 function propertySetHandle($object, $property, $value) {
   $properties=SQLSelect("SELECT ID FROM lagartoendpoints WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
      for($i=0;$i<$total;$i++) {
         $this->setProperty($properties[$i]['ID'], $value);
      }
   }
 }

 /**
 * Title
 *
 * Description
 *
 * @access public
 */
  function updateDevices() {
   $devices=SQLSelect("SELECT * FROM lagartoservers WHERE UPDATE_PERIOD>0 AND NEXT_UPDATE<=NOW()");
   $total=count($devices);
   for($i=0;$i<$total;$i++) {
    $devices[$i]['NEXT_UPDATE']=date('Y-m-d H:i:s', time()+$devices[$i]['UPDATE_PERIOD']);
    $this->refreshDevice($devices[$i]['ID']);
   }
  }


/**
* Title
*
* Description
*
* @access public
*/
 function setProperty($property_id, $value) {
  $prop=SQLSelectOne("SELECT * FROM lagartoendpoints WHERE ID='".$property_id."'");
  $prop['CURRENT_VALUE_STRING']=$value;
  SQLUpdate('lagartoendpoints', $prop);

  if ($prop['DIRECTION_STRING']=='out') {
   $device=SQLSelectOne("SELECT * FROM lagartoservers WHERE ID='".$prop['SERVER_ID']."'");

   if ($prop['TYPE_STRING']=='bin') {
    if ($value==1) {
     $value='on';
    } elseif ($value==0) {
     $value='off';
    }
   }

   $content=getURL('http://' . $device['IP'] . ':' . $device['PORT'] . '/values?id=' . $prop['ENDPOINT_ID'] . '&value='.$value, 0);
  }
  $this->readValues($prop['SERVER_ID'], $prop['ENDPOINT_ID']);

 }


/**
* lagartoendpoints search
*
* @access public
*/
 function search_lagartoendpoints(&$out) {
  require(DIR_MODULES.$this->name.'/lagartoendpoints_search.inc.php');
 }


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS lagartoservers');
  SQLExec('DROP TABLE IF EXISTS lagartoendpoints');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall() {
/*
lagartoservers - lagarto Devices
lagartoendpoints - lagarto Properties
*/
  $data = <<<EOD

 lagartoservers: ID int(10) unsigned NOT NULL auto_increment
 lagartoservers: TITLE varchar(255) NOT NULL DEFAULT ''
 lagartoservers: MDID varchar(255) NOT NULL DEFAULT ''
 lagartoservers: TYPE varchar(255) NOT NULL DEFAULT ''
 lagartoservers: PORT int(10) NOT NULL DEFAULT '0'
 lagartoservers: ZMQPORT int(10) NOT NULL DEFAULT '0'
 lagartoservers: IP varchar(255) NOT NULL DEFAULT ''
 lagartoservers: PASSWORD varchar(255) NOT NULL DEFAULT ''
 lagartoservers: UPDATE_PERIOD int(10) NOT NULL DEFAULT '0'
 lagartoservers: NEXT_UPDATE datetime

 lagartoendpoints: ID int(10) unsigned NOT NULL auto_increment
 lagartoendpoints: SERVER_ID int(10) NOT NULL DEFAULT '0'
 lagartoendpoints: ENDPOINT_ID varchar(50) NOT NULL DEFAULT '0'
 lagartoendpoints: TITLE varchar(50) NOT NULL DEFAULT '0'
 lagartoendpoints: TYPE int(3) NOT NULL DEFAULT '0'
 lagartoendpoints: CURRENT_VALUE_STRING varchar(255) NOT NULL DEFAULT ''
 lagartoendpoints: TYPE_STRING varchar(20) NOT NULL DEFAULT ''
 lagartoendpoints: DIRECTION_STRING varchar(20) NOT NULL DEFAULT ''
 lagartoendpoints: LINKED_OBJECT varchar(255) NOT NULL DEFAULT ''
 lagartoendpoints: LINKED_PROPERTY varchar(255) NOT NULL DEFAULT ''
 lagartoendpoints: LINKED_METHOD varchar(255) NOT NULL DEFAULT ''
 lagartoendpoints: UPDATED datetime

EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDA5LCAyMDE1IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
