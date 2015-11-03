<?php
// Mister Lib Foundation Library
// Copyright(C) 2006, 2007 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2011 McDaniel Consulting, LLC.  All rights resvered.
//
// MrLib Database Library - MySQL Interface
//
//
// When initializing this class, you can pass some additional data to the constructor:
//   args['persistent']      Establish a persistent connection to the database.
//   args['newlink']         Force PHP to establish a new connection to the database no matter what.
//   args['force_escape']    Force escaping of all data without checking if data has already been escaped.
//                           This applies to the do() methods only.
//

mrlib::load("sql", "MrSQL.php");

class MrMySQL extends MrSQL
{


  /* Create a new database connection and return an object representing that connection.
   * @param hostname (string)   Hostname of database server
   * @param login    (strring)  Login name to authenticate to database. server.
   * @param password (string)   Password for database server.
   * @param args     (array)    Optional array of arguments.
   *
   * Optional arguments are:
   *  newlink      - PHP will reuse connections.  Set this to 1 to guarantee a new connection.
   *  persistent   - Use persistent connections.
   *  force_escape - Something.
   * 
   */
  
   function __construct ($hostname, $login="", $password="", $database="", $args=array())
   {
      parent::__construct();
      $this->server    = $hostname;
      $this->login     = $login;
      $this->password  = $password;
      $this->database  = $database;

      if (isset($args['persistent']))
        $this->persistent = TRUE;
      else
        $this->persistent = FALSE;

      if (isset($args['newlink']))
        $this->newlink = TRUE;
      else
        $this->newlink = FALSE;

      if (isset($args['force_escape']))
        $this->forceEscape(TRUE);
      else
        $this->forceEscape(FALSE);

      $this->connect();
   }


  /* Query the database and register the result resource internally.
   * @param (string)    $query  SQL query to execute.
   * @return (resource) Result resource, if you want to use it for something.
   */

   public function query($query)
   {
      if ($this->checkConnection() == FALSE)
      {
         return FALSE;
      }

      if ($this->debugMode == TRUE)
      {
	 var_dump($query);
         trigger_error($query, E_USER_NOTICE);
      }

      $handle = $this->getHandle();
      $res = mysql_query($query, $handle);
      $this->registerResult($res);
      return $res;
   }



   public function fetchRow()
   {
      $row = mysql_fetch_row($this->getResult());
      return $row;
   }



   public function fetchArray()
   {
      $row = mysql_fetch_assoc($this->getResult());
      return $row;
   }



   public function fetchObject()
   {
      $obj = mysql_fetch_object($this->getResult());
      return $obj;
   }



   public function fetchItem($key)
   {
      if (empty($key))
      {
         return FALSE;
      }

      $res = $this->getResult();
      $row = $this->fetchArray();
      return $row[$key];
   }



   public function fetchResults($key=NULL)
   {
      $res = $this->getResult();
      $results = array();

      if ($key==NULL)
      {
         while ($row = $this->fetchArray())
         {
            $results[] = $row;
         }
      }
      else
      {
         while ($row = $this->fetchArray())
         {
            $results[$row[$key]] = $row;
         }

      }

   return $results;
   }



   public function affectedRows()
   {
      $aff_rows = @mysql_affected_rows($this->getHandle());
      return $aff_rows;
   }



   public function numRows()
   {
      $num_rows = @mysql_num_rows($this->getResult());
      return $num_rows;
   }



   public function lastInsertId()
   {
      $query = "SELECT LAST_INSERT_ID()";
      $res = @mysql_query($query, $this->getHandle());
      $row = @mysql_fetch_row($res);
      return $row[0];
   }



  /* Generate an 'INSERT ... ON DUPLICATE KEY UPDATE' SQL query and execute it.  I am pretty sure this will only
   * work with mysql (and on versions >= 4.1)
   */

   public function doDuplicateKeyInsert($table, $inputVars)
   {
      $fields_insert = "";
      $fields_update = "";
      $values = "";

      if (!is_array($inputVars) || count($inputVars) < 1)
      {
         error(E_WARNING, "doDuplicateKeyInsert() expects an array with key=value pairs as its second argument.");
         return FALSE;
      }

      foreach ($inputVars as $field => $value)
      {
         $value_converted = $this->convertValueToSQL($value);
         $fields_insert .= "$field, ";
         $fields_update .= "$field = $value_converted, ";
         $values .= "$value_converted, ";
      }

      /* remove trailing comma and space */
      $fields_insert = substr($fields_insert, 0, -2);
      $fields_update = substr($fields_update, 0, -2);
      $values = substr($values, 0, -2);

      $query = "INSERT INTO $table ($fields_insert) VALUES ($values)
                ON DUPLICATE KEY UPDATE
                $fields_update
               ";

      $res = $this->query($query);

   return $res;
   }



  /* Construct and execute an INSERT IGNORE statement.
   */

   public function doInsertIgnore($table, $inputVars=array())
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
         $fields .= "$field, ";
         $values .= $this->convertValueToSQL($value) . ", ";
      }

      /* remove trailing comma and space */
      $fields = substr($fields, 0, -2);
      $values = substr($values, 0, -2);

      $query = "INSERT IGNORE INTO $table ($fields) VALUES ($values)";
      $res = $this->query($query);

   return $res;
   }



  /* MYSQL's method of making unique inserts is INSERT IGNORE.  Use it.
   */

   public function doUniqueInsert($table, $inputVars=array())
   {
      return $this->doInsertIgnore($table, $inputVars);
   }



  /* Establish a connection to the MySQL server and select appropriate database.
   */

   protected function connect()
   {
      if ($this->persistent)
        $handle = mysql_pconnect($this->server, $this->login, $this->password);
      else
        $handle = mysql_connect($this->server, $this->login, $this->password, $this->newlink);

      if ($handle == FALSE)
      {
         return FALSE;
      }

      $res = mysql_select_db($this->database, $handle);

      if ($res == FALSE)
      {
         return FALSE;
      }

      $this->registerHandle($handle);

   return TRUE;
   }
   
   
   // Close the database connection.  This will render the connection in-operative (obviously).
   public function close()
   {
      if ($this->checkConnection())
      {
         $handle = $this->getHandle();
         mysql_close($handle);

         $this->dbhandle = NULL;
         $this->isconnected = 0;
      }
   }
   
   
/* end mysql class */
}



?>
