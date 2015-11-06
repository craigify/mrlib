<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Input Proxy Class
// Provide an abstraction layer between the actual input data passed to the script and your code.
//
//
//  Method               Description
// ------------------------------------------------------------------------------------------------
//  addInputData()       Add input data through _GET, _POST, _REQUEST, etc..
//  getVar()             Retrieve an input variable value by name.
//  getVars()            Retrieve all input variables as array of key=>value pairs.
//  setVar()             Set a variable just like it was sent to us via POST, GET, etc...
//  unsetVar()           Un-set or remove a variable from the input data.
//
//  Methods to validate input data.  Each method either returns TRUE or FALSE:
//
//  isDefined()          Return TRUE if an input variable is defined.
//  isEmpty()            Return TRUE if an input variable is empty.
//  isDefinedNotEmpty()  Return TRUE if an input variable is defined and not empty.
//  isAlphaNumeric()     Return TRUE if an input variable is all alphanumerics.
//  isEmail()            Return TRUE if an input variable is a valid email address.
//

mrlib::load("core/Validator");


class MrInputProxy
{
   protected $validateObj;
   public $inputData;
   public $stripTags;

  /* Constructor.
   */

   function __construct()
   {
      $this->stripTags = TRUE;
      $this->validateObj = new Validator();
      $this->inputData = array();
   }


   // Toggle the use of strip_tags() on the input data.  Pass in TRUE/FALSE.
   // @param (Boolean)  $val    TRUE to turn it on, FALSE to turn it off.
   public function setStripTags($val)
   {
      $this->stripTags = $val;
   }


  /* Set a variable as if it was passed to us via _POST, _GET, etc...  Beware that this will overwrite any previous
   * value of a variable by that same name.
   *
   * Returns FALSE on error, otherwise spits back out the value of the variable you just set.
   */

   public function setVar($key, $value)
   {
      $this->inputData[$key] = $value;

   return $value;
   }
   
   
  /* Set vars by passing an array of key=>value pairs.  Like setVar() but takes an entire array as an arg.
   */
   public function setVars($vars)
   {
      foreach ($vars as $key => $value)
      {
         $this->inputData[$key] = $value;
      }
   }


  /* "Un-set" or remove a variable from the input data.
   */

   public function unsetVar($key)
   {
      unset($this->inputData[$key]);
   }
   
   
  /* Clear all input vars.
   */
  
   public function clearVars()
   {
      $this->inputData = array();
   }



  /* Retrieve all input data variables as array of key=>value pairs.
   *
   * Ex:
   *  data['var1'] = "value";
   *  data['var2'] = "some other value";
   *
   */

   public function getVars()
   {
      return $this->inputData;
   }



  /* Retrieve the value of input variable by name.  Return blank string "" if variable is not set.
   */

   public function getVar($key)
   {
      if (isset($this->inputData[$key]))
      {
         return $this->inputData[$key];
      }
      else
      {
         return "";
      }
   }



  /* See if input variable has been defined.  Can accept a variable length of arguments, checking
   * if each is defined.  Returns TRUE or FALSE.
   */

   public function isDefined($arg)
   {
      foreach (func_get_args() as $index => $arg)
      {
         if (!isset($this->inputData[$arg]))
         {
            return FALSE;
         }
      }

   return TRUE;
   }



  /* Check if an input variable is empty.  Unlike the PHP empty() function which complains if a variable is not set,
   * this method considers an input variable empty if a) it is not defined or b) if it is defined and blank.
   *
   * Return TRUE if empty, FALSE if not.
   */

   public function isEmpty($var)
   {
      if (isset($this->inputData[$var]))
      {
         if (empty($this->inputData[$var]))
         {
            return TRUE;
         }
         else
         {
            return FALSE;
         }
      }
      else
      {
         return FALSE;
      }
   }



  /* Check if an input variables is both set and NOT empty.  This is similar to the addEmptyVars() validation method,
   * which ensures that an input variable is both defined and is not NULL, FALSE, an empty string, variable length
   * string of spaces (or tabs), or zero.  See PHP empty() function.
   *
   * Return TRUE is defined and not empty, otherwise return FALSE.
   */

   public function isDefinedNotEmpty($var)
   {
      if (isset($this->inputData[$var]))
      {
         if (empty($this->inputData[$var]))
         {
            return FALSE;
         }
         else
         {
            return TRUE;
         }
      }
      else
      {
         return FALSE;
      }
   }


  /* Add input data.  This grabs data from one of the various input data arrays in PHP and stores them
   * internally.   Watch for duplicate fields, as previous array keys will be overwritten.
   */

   public function addInputData($type)
   {
      switch ($type)
      {
         case "POST":
            $data = $_POST;
         break;

         case "GET":
            $data = $_GET;
         break;

         case "REQUEST":
            $data = $_REQUEST;
         break;

         default:
            $data = array();
         break;
      }

      /* Run strip_tags() to remove any PHP and HTML tags from input */
      if ($this->stripTags == TRUE)
      {
         foreach ($data as $key => $value)
         {
            $data[$key] = strip_tags($value);
         }         
      }

      $this->inputData = array_merge($this->inputData, $data);
   }



  /* Pick up any other method calls and send them to the Validator object.  If the method does not exist
   * in the Validator object, return NULL.
   *
   * We expect you to call these methods like the following:
   *
   * methodName(inputVar, arg1, arg2, arg3 ...);
   *
   */

   public function __call($method, $args)
   {
      $args[0] = $this->inputData[$args[0]];

      if (!method_exists($this->validateObj, $method))
      {
         return NULL;
      }

      return call_user_func_array(array($this->validateObj, $method), $args);
   }


/* end class */
}



?>
