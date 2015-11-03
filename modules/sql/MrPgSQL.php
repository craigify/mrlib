<?php
// Mister Lib Foundation Library
// Copyright(C) 2006, 2007 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2011 McDaniel Consulting, LLC.  All rights resvered.
//
// MrLib Database Library - PgSQL Interface
//
// When initializing this class, you can pass some additional data to the constructor:
//   args['persistent']      Establish a persistent connection to the database.
//   args['newlink']         Force PHP to establish a new connection to the database no matter what.
//   args['force_escape']    Force escaping of all data without checking if data has already been escaped.
//                           This applies to the do() methods only.
//

mrlib::load("sql", "MrSQL.php");


class MrPgSQL extends MrSQL
{

   function __construct($hostname, $login, $password, $database, $args=array())
   {
      parent::__construct();
      $this->server    = $hostname;
      $this->login     = $login;
      $this->password  = $password;
      $this->database  = $database;

      if (isset($args['persistent']) && $args['persistent'] == TRUE)
        $this->persistent = TRUE;
      else
        $this->persistent = FALSE;

      if (isset($args['newlink']) && $args['newlink'] == TRUE)
        $this->newlink = TRUE;
      else
        $this->newlink = FALSE;

      if (isset($args['force_escape']))
        $this->forceEscape(TRUE);
      else
        $this->forceEscape(FALSE);

      $this->connect();
   }



   FUNCTION connect()
   {
      $cstr = "host=" . $this->server . " user=" . $this->login . " password=" . $this->password . " dbname=" . $this->database;

      if ($this->persistent)
        $handle = pg_pconnect($cstr);
      else
        $handle = pg_connect($cstr);

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



   function __query($query)
   {
      if ($this->checkConnection() == FALSE)
      {
         return FALSE;
      }

      $res = pg_query($this->getHandle(), $query);
      $this->registerResult($res);

   return $res;
   }



   function __queryDebug($query)
   {
      global $__WTCACHE;

      if ($this->checkConnection() == FALSE)
      {
         return FALSE;
      }

      $__WTCACHE['debug']['database']['type'] = "PgSQL";
      $__WTCACHE['debug']['database']['server'] = $this->server;
      $__WTCACHE['debug']['database']['database'] = $this->database;

      $handle = $this->getHandle();
      $microtime_start = get_microtime();
      $res = pg_send_query($handle, $query);
      $microtime_end = get_microtime();

      if (!$pgRes = pg_get_result($handle))
      {
         error(E_WARNING, "Error while attempting to get result resource from previous PgSQL query.");
         return FALSE;
      }

      if ($res == FALSE)
      {
         $__WTCACHE['debug']['database']['errno'] = "Error";
         $__WTCACHE['debug']['database']['errormsg'] = pg_result_error($pgRes);
         error(E_DATABASE_FAIL, $query);
         return FALSE;
      }
      else
      {
         $__WTCACHE['debug']['database']['time'] = round($microtime_end - $microtime_start, 6);

         if (preg_match("/(INSERT)|(UPDATE)|(DELETE)|(REPLACE)/si", $query))
         {
            $__WTCACHE['debug']['database']['rows'] = pg_affected_rows($handle);
         }
         elseif (preg_match("/(SELECT)|(SHOW)/si", $query))
         {
            $__WTCACHE['debug']['database']['rows'] = pg_num_rows($pgRes);
         }
         else
         {
            $__WTCACHE['debug']['database']['rows'] = "Unknown";
         }

         error(E_DATABASE_QUERY, $query);
      }

      $this->registerResult($pgRes);

   return $res;
   }



   FUNCTION fetchRow()
   {
      $row = pg_fetch_row($this->getResult());

   RETURN $row;
   }



   FUNCTION fetchArray()
   {
      $row = pg_fetch_assoc($this->getResult());

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
      $num_rows = @pg_affected_rows($this->getHandle());

   return $num_rows;
   }



   FUNCTION numRows()
   {
      $num_rows = @pg_num_rows($this->getResult());

   return $num_rows;
   }



   FUNCTION lastInsertId()
   {
      $id = @pg_last_oid($this->getResult());

   RETURN $id;
   }


/* end pgsql class */
}


?>
