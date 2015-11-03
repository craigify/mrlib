<?php
// Mister Lib Foundation Library
// Copyright(C) 2006, 2007 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2015 McDaniel Consulting, LLC

//
// MrLib Event Handler Input Validator - Provide methods to set rules for input validation.
//
//
//  Method                Description
// --------------------------------------------------------------------------------------------------------------
//  validate()              Validate input data.  Done automatically if you setAutoValidation(TRUE).
//  validateError()         Register a validation error manually.
//  getValidationErrors()   Get all validation errors, if any, and return them in a 2D array.
//  numValidationErrors()   Return number of validation errors.  Returns 0 (zero) if none.
//  setValidateErrorType()  Set the error type (E_NOTICE) to throw on validation errors.
//  setAutoValidation()     Toggle auto validation when the execute() method is called. Default is off.
//  requireVar()            Require a variable to pass a validation method, also set invalid error message.
//  addRequiredVars()       Add a list of input data variables that must be present.
//  addEmptyVars()          Add a list of input data variables that must be non-empty.  By default this
//                          makes them required as well.


define("VERROR_TEMPLATE", 0);
define("VERROR_VAR", 1);
define("VERROR_KEY", 2);


class MrEventValidator extends MrEventAuth
{
   protected $autoValidation;
   protected $requiredVars = array();
   protected $nonEmptyVars = array();
   protected $validationMap = array();
   protected $validationErrors = array();


   function __construct()
   {
      parent::__construct();
      $this->autoValidation = FALSE;
   }



  /* Validate the input data.  Does some basic checking.  Any specific checking must be done manually
   * by the numerous data type validation methods.  This can be done within the validation function in
   * the front controller itself.
   *
   * When setAutoValidation(TRUE) is called, execute() will call this method automatically.
   *
   * Return TRUE if validation passes.
   * Return FALSE if validation fails.
   */

   public function validate()
   {
      $eventName = $this->eventName;

      if (isset($this->nonEmptyVars[$eventName]))
      {
         if (!$this->checkEmptyVars())
         {
            return FALSE;
         }
      }

      if (isset($this->requiredVars[$eventName]))
      {
         if (!$this->checkRequiredVars())
         {
            return FALSE;
         }
      }

      if (isset($this->validationMap[$eventName]))
      {
         if (!$this->checkValidationMap())
         {
            return FALSE;
         }
      }

      /* Check for any other validation errors that have been entered in manually. */
      if (isset($this->validationErrors[$eventName]))
      {
         return FALSE;
      }

   return TRUE;
   }



  /* Turn on/off auto validation.  If on, the input data is automatically validated when an event is executed with
   * execute().  By default this is turned off.
   *
   * If auto validation is off, you must call the validate() method accordingly in your code to validate your data.
   */

   public function setAutoValidation($toggle)
   {
      switch ($toggle)
      {
         case TRUE:
           $this->autoValidation = TRUE;
         break;

         case FALSE:
           $this->autoValidation = FALSE;
         break;

         default:
           $this->autoValidation = FALSE;
         break;
      }
   }



  /* Register a validation error about a certain in put variable $var.  Pass validation error text in $message.
   */

   public function validateError($var, $message)
   {
      $eventName = $this->eventName;
      $data['message'] = $message;
      $data['var'] = $var;

      $this->validationErrors[$eventName][] = $data;
   }



  /* Backwards-compatability.  Depreciated usage.
   */

   public function registerValidationError($var, $message)
   {
      $this->validateError($var, $message);
   }



  /* Define variable names that must be present in input data for your method to execute().
   *
   * 1. Pass just keys:
   *     $vars = array("required_var1", "required_var2", "required_var3");
   *     addRequiredVars($vars);
   *
   * 2. Pass keys and names (useful for displaying human-readable validation errors):
   *     $vars['required_var1'] = "Required variable #1";
   *     $vars['required_var2'] = "Required variable #2";
   *     $vars['required_var3'] = "Required variable #3";
   *     addRequiredVars($vars);
   *
   * 3. Pass keys as function arguments (identical to method #1)
   *     addRequiredVars("required_var1", "required_var2", "required_var3");
   */

   public function addRequiredVars($vars=array())
   {
      $eventName = $this->eventName;

      if (!is_array($vars))
      {
         unset($vars);
         foreach (func_get_args() as $index => $arg)
         {
            $vars[] = $arg;
         }
      }

      foreach ($vars as $key => $value)
      {
         if (is_int($key))
           $this->requiredVars[$eventName][$value] = $value;
         else
           $this->requiredVars[$eventName][$key] = $value;
      }

   return TRUE;
   }



  /* Define variable names that must be present, and NOT EMPTY, for your method to execute().
   *
   * This means an input variable cannot be defined as NULL, FALSE, an empty string, variable length
   * string of spaces (or tabs), or zero.  See PHP empty() function.
   *
   * Works the same was as addRequiredVars(), so see comments for usage.
   */

   public function addEmptyVars($vars=array())
   {
      $eventName = $this->eventName;

      if (!is_array($vars))
      {
         unset($vars);
         foreach (func_get_args() as $index => $arg)
         {
            $vars[] = $arg;
         }
      }

      /* First make them required */
      $this->addRequiredVars($vars);

      /* Not empty too */
      foreach ($vars as $key => $value)
      {
         if (is_int($key))
           $this->nonEmptyVars[$eventName][$value] = $value;
         else
           $this->nonEmptyVars[$eventName][$key] = $value;
      }

   return TRUE;
   }



  /* Shorthand version.  Accepts one variable at a time.
   */

   public function addRequiredVar($var)
   {
      $this->addRequiredVars($var);
   }



  /* Shorthand version.  Accepts one variable at a time.
   */

   public function addEmptyVar($var)
   {
      $this->addRequiredVars($var);
   }



  /* Require an input variable to pass a validation method (in eventDataLayer or in the Validator utility class).
   * If the input var fails, return the specified $message as a validation error.
   *
   * Example requires input var 'myvar' to be a valid email address:
   *
   *  requireVar("myvar", "isEMail", "Invalid email")
   *
   *
   * TODO: Allow inverse methods, and the ability to specify arguments??  Maybe user does this manually
   * for speed decrease in adding the additional functionality:
   *
   *   ie:  '!isEmail'   and 'isNumberRange(0, 100)'
   */

   public function requireVar($varName, $validateMethod, $message=NULL)
   {
      $eventName = $this->eventName;
      $map['var'] = $varName;
      $map['method'] = $validateMethod;
      $map['message'] = $message;

      $this->validationMap[$eventName][] = $map;
   }



  /* Return any errors in the validation process.  If there were no errors, return empty array.
   *
   * You can either return the variables as a 2D array, or as template vars that can be directly placed into the template
   * file to be parsed as you see fit.
   *
   * type VERROR_KEY:
   *  Returns an array of key => value pairs.  Key is the invalid input var and value is the message.
   *   data['name'] = "Error message goes here";
   *   data['address'] = "Error message goes here";
   *
   * type VERROR_VAR:
   *   Returns a 2D array.  Useful for sending each row to the template parser.
   *    data[i]['var']      Variable name.
   *    data[i]['message']   Detail error message, useful to display.
   *
   * type VERROR_TEMPLATE:
   *   Returns array of template vars with the offending variable name.  Names are always uppercase.
   *    data['VERROR_NAME'] = "Error message goes here"
   *    data['VERROR_ADDRESS'] = "Error message goes here"
   */

   public function getValidationErrors($type=VERROR_KEY, $eventName=NULL, $prefix="VERROR_")
   {
      if ($eventName == NULL)
      {
         $eventName = $this->eventName;
      }
      else
      {
         $eventName = $eventName;
      }

      if (!isset($this->validationErrors[$eventName]))
      {
         return array();
      }

      $errors = array();

      switch ($type)
      {
         case VERROR_KEY:
            foreach ($this->validationErrors[$eventName] as $error)
            {
               $errors[$error['var']] = $error['message'];
            }
         break;

         case VERROR_VAR:
            $errors = $this->validationErrors[$eventName];
         break;

         case VERROR_TEMPLATE:
            foreach ($this->validationErrors[$eventName] as $error)
            {
               $index = strtoupper($prefix . $error['var']);
               $errors[$index] = $error['message'];
            }
         break;

         default:
            $errors = array();
         break;
      }

   return $errors;
   }



  /* Return number of validation errors.  If there were none, return zero.
   */

   public function numValidationErrors()
   {
      $eventName = $this->eventName;

      if (isset($this->validationErrors[$eventName]))
        return count($this->validationErrors[$eventName]);
      else
        return 0;
   }



  /* Currently not implemented.
   */

   public function clearValidationData()
   {
   }



   /////////////////////////////////////////////////////////////////////////////////////////////////////
   // Internal Methods
   /////////////////////////////////////////////////////////////////////////////////////////////////////



  /* Check that required vars are present. validate() calls this method automatically.
   *
   * Return TRUE if all required vars are set.  Return FALSE if any vars are missing.
   */

   protected function checkRequiredVars()
   {
      $eventName = $this->eventName;
      $count = 0;

      foreach ($this->requiredVars[$eventName] as $key => $value)
      {
         if (!$this->inputProxy->isDefinedNotEmpty($key))
         {
            $this->validateError($key, "$value not provided");
            $count++;
         }
      }

      if ($count > 0)
        return FALSE;
      else
        return TRUE;
   }



  /* Check that required vars are present and not empty. validate() calls this method automatically.
   *
   * Return TRUE if all required vars are defined and not empty.  Return FALSE if any vars are missing/empty.
   */

   protected function checkEmptyVars()
   {
      $eventName = $this->eventName;
      $count = 0;

      foreach ($this->nonEmptyVars[$eventName] as $key => $value)
      {
         if (!$this->inputProxy->isDefinedNotEmpty($key))
         {
            $this->validateError($key, "$value not provided");
            $count++;
         }
      }

      if ($count > 0)
        return FALSE;
      else
        return TRUE;
   }



  /* Check the validation map.  Return FALSE if any variables fail, TRUE if all pass.
   */

   protected function checkValidationMap()
   {
      $eventName = $this->eventName;
      $count = 0;

      foreach ($this->validationMap[$eventName] as $map)
      {
         $var = $map['var'];
         $method = $map['method'];
         $message = $map['message'];

         /* Call validation method */
         $args[0] = $map['var'];
         $result = call_user_func_array(array($this, $method), $args);

         if ($result === FALSE)
         {
            $this->validateError($var, $message);
            $count++;
         }

         if ($result === NULL)
         {
            $oops = "No validation method '$method' found when attemping to process validation map for variable '$var'";
            $this->validateError($var, $oops);
            $count++;
         }
      }

      if ($count > 0)
        return FALSE;
      else
        return TRUE;
   }



/* end EventValidator class */
}



?>
