<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrInputProxyJSON - Extend the default input proxy, and automatically decode any JSON in input data.
//

mrlib::load("proxy/MrInputProxy");

class MrInputProxyJSON extends MrInputProxy
{
   function __construct()
   {
      parent::__construct();
   }


   // Override addInputData() to decode any JSON in input vars.
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

      foreach ($data as $key => $value)
      {         
         $valueDec = json_decode($value, TRUE);

         if (empty($valueDec))
         {
            $valueDec = $value;
         }

         $this->inputData[$key] = $valueDec;
      }
   }


// end class
}


?>
