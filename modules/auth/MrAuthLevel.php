<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Authentication Layer
//
// This class represents an ACL definition, or as we call it, an Auth Level definition.
// These are arbitary names that make up your ACL list in your application.  You define
// each auth level and assign it your desired functionality.

mrlib::load("sql/MrORM");

class MrAuthLevel extends MrORM
{
   public $id;
   public $identifier;
   public $name;
   
   function __construct()
   {
      MrORM::__construct();
      MrORM::addMap("id",           "auth_level_id");
      MrORM::addMap("identifier",   "auth_level_identifier");
      MrORM::addMap("name",         "auth_level_name");
      $this->addPrimaryKey("id");
   }
   
}
 
   
?>
