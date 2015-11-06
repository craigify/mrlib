<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrLib Asynchronous Event Handler Base class.
//
//
// * Base class for all Asynchronous Event Handlers.  Define (and extend) some methods for returning event
//   responses in various types of data formats (like JSON, XML, AMF, whatever).  Higher level class
//   MUST provide at least these two methods:
//
//   Requuired Method    Description
//   ------------------------------------------------------------------------------------------------------
//   add()               Adds a data type to the response.  Must auto-detect type of variable being passed.
//   displayEncoded()    Displays the encoded dataset to standard output.
//
//
// * This handler works similarly to the original EventHandler except that the event function must return
//   TRUE or FALSE explicitly, signifying whether the event was successful or not.
//
//   Example for event "testme":
//
//     function _event_testme(&$eh)
//     {
//        /* Do some stuff */
//        return TRUE;
//     }
//
//
// * Resultsets will include the following variables (in addition to what you return, of course):
//
//   Variable Name      Type         Desc
//   ----------------------------------------------------------------------------------------------------
//   result             Always       Result of event.  Will be either "success" or "error"
//   numErrors          Always       Number of errors.  Zero if none.
//   numAuthErrors      Always       Number of auth errors.  Zero if none.
//   numValidateErrors  Always       Number of validation errors.  Zero if none.
//   errors             Sometimes    Array of error messages.  Only defined if errors present.
//   authErrors         Sometimes    Array of auth errors.  Only defined if authentication failed.
//   validateErrors     Sometimes    Array of validation errors.  Only defined if validation failed.
//
//
// EventHandlerAsyncBase provides the following methods:
//
// Method              Description
// ------------------------------------------------------------------------------------------------------
// execute()           New event execute() method to execute a method.
// setAutoLogin()      Disable the setting of auto login.  This method overrides the original.
// error()             Sends errors to client as well as to error & debugging system.
// returnResult()      Return the resultset to the client (in whatever format) and stop execution.
// setDebugDisplay()   Toggle the displaying of debug information by passing TRUE.  Default FALSE.
//

mrlib::load("event/MrEventHandler");


abstract class MrEventHandlerAsync extends MrEventHandler
{
   protected $contentType;



   function _constructor()
   {
      EventHandler::__construct();
      $this->contentType = "";
   }



   //////////////////////////////////////////////////////////////////////////////////////////////////////
   // These must be overridden by a higher level class.
   //////////////////////////////////////////////////////////////////////////////////////////////////////


   /* Add a PHP data type to the resultset.  Auto-detect the variable type */
   abstract protected function add($var_name, $var_data);


   /* Display encoded resultset. */
   abstract protected function displayEncoded();





   //////////////////////////////////////////////////////////////////////////////////////////////////////
   // Other changes on top of MrEventHandler below
   //////////////////////////////////////////////////////////////////////////////////////////////////////



  /* Execute an async broker request method.  We override EventHandler::execute() here.
   */

   public function execute($eventName=NULL)
   {
      if ($eventName == NULL)
      {
         $eventName = $this->eventName;
      }

      /* Get function names */
      if (!$eventName || !function_exists($this->functionPrefix . $eventName))
      {
         $func = $this->functionPrefix . $this->defaultEvent;
         $event = $this->defaultEvent;

         if (!function_exists($func))
         {
            $this->error($this->errorType, "Default function '{$func}()' is undefined.  Doing nothing...");
            $this->returnResult(FALSE);
         }
      }
      else
      {
         $func = $this->functionPrefix . $eventName;
         $event = $eventName;
      }

      if ($func == NULL)
      {
         $this->error($this->errorType, "No event string was passed and no default event was defined.  Doing nothing...");
         $this->returnResult(FALSE);
      }

      /* Call authentication function to set any auth rules if necessary. */
      $this->executeAuthFunc($func);


      /* Call validation function to set any validation rules if necessary. */
      $this->executeValidationFunc($func);


      /* Check authentication credentials */
      if ($this->autoAuth === TRUE)
      {
         if ($this->checkExecuteAuthentication($func))
         {
            /* Currently there will only be one auth error */
            $this->error($this->authErrorType, "Authentication error(s) were detected");
            $authErrors[] = "Insufficient authentication credentials";

            $this->add("authErrors", $authErrors);
            $this->add("numAuthErrors", 1);
            //$this->add("debugHTML", error_generate_html());
            $this->returnResult(FALSE);
         }
         else
         {
            $this->add("numAuthErrors", 0);
         }
      }
      else
      {
         $this->add("numAuthErrors", 0);
      }


      /* Validate input data */
      if ($this->autoValidation === TRUE)
      {
         if (!$this->checkExecuteValidation($func))
         {
            $this->error($this->validateErrorType, "Validation error(s) were detected");
            $this->add("validateErrors", $this->getValidationErrors(VERROR_KEY));
            $this->add("numValidateErrors", $this->numValidationErrors());
            //$this->add("debugHTML", error_generate_html());
            $this->returnResult(FALSE);
         }
         else
         {
            $this->add("numValidateErrors", 0);
         }
      }
      else
      {
         $this->add("numValidateErrors", 0);
      }


      /* Execute event function */
      $eventResult = $func($this);

      if ($eventResult === TRUE)
      {
         //$this->add("debugHTML", error_generate_html());
         $this->returnResult(TRUE);
      }
      else if ($eventResult === FALSE)
      {
         //$this->add("debugHTML", error_generate_html());
         $this->returnResult(FALSE);
      }
      else
      {
         $this->error($this->errorType, "Event function did not return TRUE or FALSE explicitly, so FALSE was assumed, signifiying failure.  This is not what you want.");
         //$this->add("debugHTML", error_generate_html());
         $this->returnResult(FALSE);
      }
   }



  /* The auto login feature is not possible with this event handler.  Override AuthHandler::setAutoLogin()
   * to do nothing.
   */

   public function setAutoLogin($toggle)
   {
      return TRUE;
   }



  /* Set the Content-type header.  This will automatically be set depending on the top level handler you are using, but
   * you can use this to override the value.
   */
   public function setContentType($type)
   {
      $this->contentType = $type;
   }



  /* Return the resultset of the event to the client.  Passing TRUE will return a result of success, FALSE
   * will return a response of fail.
   */

   protected function returnResult($resType=TRUE)
   {
      if ($resType === TRUE) $this->add("result", "success");
      if ($resType === FALSE) $this->add("result", "fail");

      $numErrors = count($this->errors);

      if ($numErrors > 0)
      {
         $this->add("errors", $this->errors);
      }

      $this->add("numErrors", $numErrors);
      $this->displayEncoded();

      /* After response is displayed, we're done */
      $this->stopExecution();
   }







/* End EventHandlerAsyncBase Class */
}



?>
