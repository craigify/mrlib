<?php
// Mister Lib Foundation Library
// Copyright(C) 2006, 2007 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2011 McDaniel Consulting, LLC.  All rights resvered.
//
// MrLib Database Library - ODBC Interface
//
// When initializing this class, you can pass some additional data to the constructor:
//   args['persistent']      Establish a persistent connection to the database.
//   args['force_escape']    Force escaping of all data without checking if data has already been escaped.
//                           This applies to the do() methods only.
//

mrlib::load("sql", "MrSQL.php");

class MrODBC extends MrSQL
{

   function __construct($dsn, $username="", $password="", $args=array())
   {
      parent::__construct();

      $this->dsn = $dsn;
      $this->username = $username;
      $this->password = $password;

      if (isset($args['persistent']) && $args['persistent'] == TRUE)
        $this->persistent = TRUE;
      else
        $this->persistent = FALSE;

      if (isset($args['force_escape']))
        $this->forceEscape(TRUE);
      else
        $this->forceEscape(FALSE);

      $this->connect();
   }



  /* Establish a connection to the MySQL server and select appropriate database.
   */

   FUNCTION connect()
   {
      if ($this->persistent)
        $handle = odbc_pconnect($this->dsn, $this->username, $this->password);
      else
        $handle = odbc_connect($this->dsn, $this->username, $this->password);

      if ($handle == FALSE)
      {
         return FALSE;
      }

      $this->registerHandle($handle);

   RETURN TRUE;
   }



  /* Query the database.  As you can see, we check for debug mode first so that we optimize for speed when not
   * in debug mode.  I figure one conditional and a branch is faster than 10 conditionals.  Well, I hope at least.
   */

   function query($query)
   {
      global $__CONFIG;

      if ($__CONFIG['debug_mode'] > 0)
      {
         return $this->__queryDebug($query);
      }
      else
      {
         return $this->__query($query);
      }
   }



   FUNCTION __query($query)
   {
      global $__WTCACHE;

      if ($this->checkConnection() == FALSE)
      {
         return FALSE;
      }

      $handle = $this->getHandle();
      $res = @odbc_exec($handle, $query);
      $this->registerResult($res);

   RETURN $res;
   }



   FUNCTION __queryDebug($query)
   {
      global $__WTCACHE;

      if ($this->checkConnection() == FALSE)
      {
         return FALSE;
      }

      $__WTCACHE['debug']['database']['type'] = "ODBC";
      $__WTCACHE['debug']['database']['server'] = $this->dsn;
      $__WTCACHE['debug']['database']['database'] = "";

      $handle = $this->getHandle();
      $microtime_start = get_microtime();
      $res = @odbc_exec($handle, $query);
      $microtime_end = get_microtime();

      if ($res == FALSE)
      {
         $__WTCACHE['debug']['database']['errno'] = odbc_error($handle);
         $__WTCACHE['debug']['database']['errormsg'] = odbc_errormsg($handle);
         error(E_DATABASE_FAIL, $query);
         return FALSE;
      }
      else
      {
         $__WTCACHE['debug']['database']['time'] = round($microtime_end - $microtime_start, 6);
         $rows = odbc_num_rows($res);

         if ($rows > 0)
           $__WTCACHE['debug']['database']['rows'] = $rows;
         else
           $__WTCACHE['debug']['database']['rows'] = "Unknown";

         error(E_DATABASE_QUERY, $query);
      }

      $this->registerResult($res);

   RETURN $res;
   }



   FUNCTION fetchRow()
   {
      $row = odbc_fetch_row($this->getResult());

   RETURN $row;
   }



   FUNCTION fetchArray()
   {
      $row = odbc_fetch_array($this->getResult());

   RETURN $row;
   }



  /* Fetch a specific key from a single resultset.
   */

   FUNCTION fetchItem($key)
   {
      if (empty($key))
      {
         error(E_NOTICE, "Key '$key' was undefined in resultset");
         return FALSE;
      }

      $res = $this->getResult();
      $row = $this->fetchArray();

   RETURN $row[$key];
   }



  /* Return the entire resultset as a 2D associative array.  Key represents the field you wish
   * to use on the top level array.  If no key is specified, the array is formed sequentially
   * starting with zero, as usual.
   */

   FUNCTION fetchResults($key=NULL)
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

   RETURN $results;
   }



   FUNCTION affectedRows()
   {
      $num_rows = @odbc_num_rows($this->getHandle());

   return $num_rows;
   }



   FUNCTION numRows()
   {
      $num_rows = @odbc_num_rows($this->getResult());

   return $num_rows;
   }


   // how to do this?
   FUNCTION lastInsertId()
   {
   }


/* end odbc class */
}


?>
