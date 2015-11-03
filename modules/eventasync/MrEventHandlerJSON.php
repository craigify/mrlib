<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrLib JSON Event Handler - Define events and automatically output in JSON
//
// This event handler can receive JSON-encoded strings as input vars.  For example, you can pass it
// key=<JSON encoded string> and it will automatically decode the string and place it in the inputVars
// array to be retrieved with the getVar() method.
//
//

mrlib::load("eventasync", "MrEventHandlerAsync.php");


class MrEventHandlerJSON extends MrEventHandlerAsync
{
   public $encodedStr;
   protected $numVars;
   protected $isEncoded;
   protected $inVars = array();


   /* Constructor */
   function __construct()
   {
      MrEventHandlerAsync::__construct();
      $this->isEncoded = FALSE;
      $this->contentType = "application/json";
   }



  /* Add a PHP data type to this JSON object.  Auto detect the type.
   */

   public function add($var_name, $var_data)
   {
      if (is_string($var_data) || is_numeric($var_data) || is_array($var_data) || is_array($var_data) || is_object($var_data) || is_bool($var_data))
      {
         $this->numVars++;
         $this->inVars[$var_name] = $var_data;
      }
      else
      {
         return FALSE;
      }

   return TRUE;
   }



  /* Override.  Use our own addInputData method below.
   */

   public function setEventHandler($type, $var)
   {
      /* Set and clean input data */
      $this->addInputData($type);

      /* Get event name */
      if (isset($this->inputData[$var]))
        $this->eventName = $this->inputData[$var];
   }



  /* Display the JSON encoded resultset.
   */

   public function displayEncoded()
   {
      $this->getEncoded();
      
      if (!empty($this->contentType))
      {
         header("Content-type: " . $this->contentType);
      }
      else
      {
         header("Content-type: application/json");         
      }

      echo $this->encodedStr;
   }



  /* Return the JSON object as a string.  Use PHP's built in json support (mmm nice fast C code)
   */

   public function getEncoded()
   {      
      if (function_exists("json_encode"))
      {
         $this->encodedStr = json_encode($this->inVars);
      }
      else
      {
         $json = new SimpleJSON();
         $json->inVars = $this->inVars;
         $json->numVars = $this->numVars;
         $this->encodedStr = $json->getEncoded();
      }

   return $this->encodedStr;
   }
   
   
   
   /* Override MrEventDataLayer::addInputData() to accept JSON-encoded key=value pairs as well.  Automatically decode any JSON
   * input variables and add them to the inputData array for later retrieval through normal methods.
   */

   protected function addInputData($type)
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


     /* Run strip_tags() to remove any PHP and HTML tags from input and decode any JSON strings 
      *
      * Since PHP 5.2.11 (or somewhere around) json_decode() would return NULL if a json string could not be decoded.  It used
      * to just pass the original value though.  This way is slower, but not sure of a better workaround yet.
      * - Craig
      */

      foreach ($data as $key => $value)
      {
         $valueDec = $this->getDecoded(strip_tags($value));

         if (empty($valueDec))
         {
            $valueDec = $value;
         }

         $this->inputData[$key] = $valueDec;
      }
   }



  /* Decode a JSON string and return the resulting value as an array.
   */

   private function getDecoded($jsonData)
   {
      if (function_exists("json_decode"))
      {
         return json_decode(utf8_decode($jsonData));
      }
      else
      {
         return $jsonData;
      }

   
   }



/* End EventHandlerJSON class */
}



?>
