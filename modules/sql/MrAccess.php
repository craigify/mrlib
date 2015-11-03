<?php
// Mister Lib Foundation Library
// Copyright(C) 2006, 2007 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2011 McDaniel Consulting, LLC.  All rights resvered.
//
// MrLib Database Library
//
// This is a simple extension of the ODBC database library.  The doInsert() method is overridden so that
// SQL INSERT statements have field names included in brackets.  Any other MS Access specific code might
// also wind up in here.
//
// * What about reading MS Access files directly without using ODBC?  This could be useful code that would
//   logically be placed in here.
//
//
// ******************** THIS IS TEST CODE ONLY. ***********************
//
//

webtemplate_include("core", "sql/sql_generic.php");
webtemplate_include("core", "sql/odbc.php");
webtemplate_include("core", "util/date.php");


CLASS AccessDB EXTENDS ODBC
{

  /* Constructor.
   */

   FUNCTION AccessDB($dsn, $username="", $password="", $args=array())
   {
      $this->ODBC($dsn, $username, $password, $args);
   }



  /* Override SQLGeneric::doInsert()
   */

   function doInsert($table, $inputVars=array())
   {
      $fields = "";
      $values = "";

      if (!is_array($inputVars) || count($inputVars) < 1)
      {
         error(E_WARNING, "doInsert() expects an array with key=value pairs as its second argument.");
         return FALSE;
      }

      foreach ($inputVars as $field => $value)
      {
         $fields .= "[$field], ";   // include field names in brackets
         $values .= "'$value', ";
      }

      /* remove trailing comma and space */
      $fields = substr($fields, 0, -2);
      $values = substr($values, 0, -2);

      $query = "INSERT INTO $table ($fields) VALUES ($values)";
      $res = $this->query($query);

   return $res;
   }


/* end odbc class */
}


?>
