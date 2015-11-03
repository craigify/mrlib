<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Output Proxy
//
// The OutputProxy class provides a method for setting output data and delivering output data to
// the client.  It is automatically attached to your controller class by the $this->outputProxy
// variable and should always be used to handle output.  Don't echo() directly.
//
//
//  Method               Description
// ------------------------------------------------------------------------------------------------
// setContentType()      Set the content type.  Defaults to 'text/html'
// addHeader()           Add a header to be displayed when output is sent to client.
// setOutput()           Set the output buffer.
// appendOutput()        Append to the existing output buffer.
// getOutput()           Return output buffer & headers as a string.
// clearOutput()         Clear output buffer.
// displayOutput()       Display output buffer to client.
//

class MrOutputProxy
{
   protected $outputData;
   protected $contentType;
   protected $headers;


   // Constructor
   function __construct()
   {
      $this->outputData = "";
      $this->headers = array();
      $this->contentType = "text/html";
      ob_start();
   }


   // Set the content type.
   // @param $type (string) Content type identifier, such as 'text/html'  or 'application/json' etc...
   public function setContentType($type)
   {
      $this->contentType = $type;
   }
   
   
   // Add a header for output.  Specify full header string.
   // @param $header (string)  Properly formatted HTTP header string.
   public function addHeader($header)
   {
      array_push($this->headers, $header);
   }


   // Display header data.
   // @return $headers (string)  String of formatted header data.
   public function displayHeaders()
   {
      header("Content-Type: {$this->contentType}");
      
      foreach ($this->headers as $header)
      {
         header($header);
      }      
   }
   
   
   // When using the default output proxy, this simply results in a print_r() dump in the output
   // buffer.  Consider using a specific output proxy like MrOutputProxyJSON, or use the methods
   // setOutput() or appendOutput() to set or append the output buffer.
   //
   // @param $key (string) key of resultset
   // @param $value (string)  Value of key to store in resultset.
   public function add($key, $value)
   {
      $resultset[$key] = $value;
      $output = "<pre>";
      $output .= print_r($resultset, TRUE);
      $output .= "</pre>";
      $this->appendOutput($output);
   }
   
   
   // Set the output data by passing in a string.  We store the data in memory until the
   // event handler calls the getOutput() method to send to the data to the client.  Subsequent
   // calls to this method override any previous output data stored in memory.
   //
   // @param $data (string)  Output data to be set.
   public function setOutput($output)
   {
      $this->outputData = $output;
   }



   // Append output data to the buffer memory.
   // @param $data (string)  Data to append to output buffer memory.
   public function appendOutput($output)
   {
      $this->outputData .= $output;
   }
   
   
   // Clear all output data, including headers.
   public function clearOutput()
   {
      $this->headers = array();
      $this->outputData = "";
   }


   // Get output buffer.
   // @return $output (string) The output buffer.
   public function getOutput()
   {
      return $this->outputData;
   }

   
   // Echo the output buffer to stdout, or back to the web server for sending to the client.
   public function displayOutput()
   {
      echo $this->displayHeaders();
      echo $this->getOutput();
      ob_end_flush();
   }


/* end class */
}



?>
