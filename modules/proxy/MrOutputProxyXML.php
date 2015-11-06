<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// XML Output Proxy
// Create XML representations of PHP data types.  Try to avoid using sequential arrays.
//
// NOTE: Use add() to add a data type to the resultset.


mrlib::load("proxy/MrOutputProxy");

class MrOutputProxyXML extends MrOutputProxy
{
   protected $inVars;
   protected $numVars;
   protected $writer;


   // Constructor
   function __construct()
   {
      parent::__construct();
      $this->writer = new XMLWriter();

      $this->setContentType("text/xml");
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
      $this->writer->openMemory();
      $this->writer->startDocument("1.0");
      $this->encode($this->inVars, "result", "root");
      return $this->writer->flush();
   }


   // Recursively step through a data type and generate an XML document based on it.
   // @param mixes   $data   The PHP data type to encode.  This is would 99.9% be $this->inVars
   // @param string  $node   The name of the root node.
   private function encode($data, $node)
   {
      if (is_numeric($node))
      {
         $node = "element";
      }

      $this->writer->startElement($node);
      
      foreach ($data as $key => $val)
      {
         if (is_array($val) || is_object($val))
         {
            self::encode($val, $key);
         }
         else
         {
            if (is_numeric($key))
            {
               $key = "element";
            }
            
            $this->writer->startElement($key);
            
            if (is_string($val))
            {
               $this->writer->writeCdata($val);
            }
            else
            {
               $this->writer->text($var);               
            }

            $this->writer->endElement();
         }
      }

      $this->writer->endElement();      
   }
   

   // Not used.
   private function encodeManual($data, $node, $depth)
   {
      $xml .= str_repeat("\t", $depth);
      $xml .= "<$node>\n";
      
      foreach($data as $key => $val)
      {
         if (is_array($val) || is_object($val))
         {
            $xml .= self::encode($val, $key, ($depth + 1));
         }
         else
         {
            $xml .= str_repeat("\t", ($depth + 1));
            $xml .= "<$key>" . htmlspecialchars($val) . "</$key>\n";
         }
      }
      
      $xml .= str_repeat("\t", $depth);
      $xml .= "</$node>\n";
      return $xml;
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
