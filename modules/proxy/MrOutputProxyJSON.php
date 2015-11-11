<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// JSON Output Proxy
//
// Provide methods for adding and encoding JSON data to the client.  Instead of sending over a stream of output data, we send over a formatted result object
// encoded in JSON.
//
// NOTE: Instead of using setOutput() and appendOutput(), use add() to add a data type to the resultset.  
// 
//

mrlib::load("proxy/MrOutputProxy");


class MrOutputProxyJSON extends MrOutputProxy
{
   protected $inVars;
   protected $numVars;


   // Constructor
   function __construct()
   {
      parent::__construct();
      $this->setContentType("application/json");
      $this->inVars = array();
      $this->numVars = 0;
   }


   // Add a PHP data type to the resultset to be encoded.
   //
   // outputProxy->add("myNumber", 12345);
   // outputProxy->add("myArray", $myArr);
   // outputProxy->add("myObject", $myObj);
   //
   // @param $key (string)  Name of JSON variable
   // @param $value (string)  Value of JSON variable.
   public function add($key, $value)
   {
      if (is_string($value) || is_numeric($value) || is_array($value) || is_array($value) || is_object($value) || is_bool($value))
      {
         $this->numVars++;
         $this->inVars[$key] = $value;
      }
      else
      {
         return FALSE;
      }

   return TRUE;
   }


   // Encode the resultset and return it as a string.
   // @return $encodeStr (string) JSON encoded result string.
   public function getEncoded()
   {
      // Just return empty string if our input data is empty.
      if (empty($this->inVars))
      {
         return "";
      }
      
      $encodedStr = json_encode($this->inVars);
      return $encodedStr;
   }



   //
   // OVERRIDE METHODS
   //


   // Disable these methods.  Use add() instead.
   public function setOutput($text) { }
   public function appendOutput($text) { }

   
   // Clear all output data, including headers.
   public function clearOutput()
   {
      $this->headers = array();
      $this->outputData = "";
      $this->inVars = array();
      $this->numVars = 0;
   }


   // Get output buffer.
   // @return $output (string) The output buffer as a JSON encoded string.
   public function getOutput()
   {
      error_reporting(0);
      return $this->getEncoded();
   }

   



/* end class */
}



?>
