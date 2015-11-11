<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Output Proxy
//
// The OutputProxy class provides a method for setting output data and delivering output data to the client.  It is automatically attached to your controller
// class by the $this->outputProxy variable and should always be used to handle output.  Don't echo() directly.
//

class MrOutputProxy
{
   protected $outputData;
   protected $contentType;
   protected $headers;
   protected $httpResponseCode;
   

   // Constructor
   function __construct()
   {
      $this->outputData = "";
      $this->headers = array();
      $this->contentType = "text/html";
      $this->httpResponseCode = 200;
      ob_start();
   }


   // Set the content type.
   // @param $type (string) Content type identifier, such as 'text/html'  or 'application/json' etc...
   public function setContentType($type)
   {
      $this->contentType = $type;
   }
   
   
   // Set the HTTP response code that will be sent to the client when displayOutput() is called
   // @param $code (int)  The HTTP Response code to use
   public function setHttpResponseCode($code)
   {
      $this->httpResponseCode = $code;
   }
   
   
   // Return the current HTTP response code that will be sent to the client.
   public function getHttpResponseCode()
   {
      return $this->httpResponseCode;
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
   
   
   // If you have an associative array of key=>value pairs, you can pass the entire array to this method so it can add each key to the result set for you.
   // Note: If you have an object, just type cast it to an array before sending it to this method.
   // @param $arr (array)  Array of key=> value pairs to add to output.
   public function addFromAssoc($arr)
   {
      foreach ($arr as $key => $value)
      {
         $this->add($key, $value);
      }
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
      http_response_code($this->httpResponseCode);
      echo $this->displayHeaders();
      echo $this->getOutput();
      ob_end_flush();
   }





/* end class */
}



?>
