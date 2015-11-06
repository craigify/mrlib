<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrLib Template System - Template Function Manager
//

mrlib::load("core/MrSingleton.php");


class MrFunctionManager extends MrSingleton
{
    private $functions;
    
    
    function __construct()
    {
        $this->functions = array();
    }



   // Attempt to call a registered template function.
   // @return mixed
   public function callFunction($func, $args=array())
   {
      $isValid = FALSE;

      /* Check if function is registered */
      foreach ($this->functions as $registeredFunc => $funcName)
      {
         if ($registeredFunc == $func)
         {
            $isValid = TRUE;
            break;
         }
      }

      if ($isValid != TRUE)
      {
         $m = "Attempted to call template function '{$func}' but it was not previously registered with template_register_func()";
         return $m;
      }

      /* Make sure function is callable */
      if (!function_exists($funcName))
      {
         $m = "Attempted to call registered template function '{$func}' but the callback function '{$funcName}' does not exist in my scope.";
         return $m;
      }

      /* Call function and return results */
      $res = call_user_func_array($funcName, $args);

   return $res;
   }




    // Register a callable function with the template parser.
    //
    // func_name is the PHP function as it is defined (needs to be in global scope)
    // template_func_name is the name used to call it within the template.
    //

    public function register($template_func_name, $php_func_name)
    {
        $template_func_name = strtoupper($template_func_name);
        $this->functions[$template_func_name] = $php_func_name;

    return TRUE;
    }



    // Unregister a template function
    public function unregister($template_func_name)
    {
        $template_func_name = strtoupper($template_func_name);
        
        if (isset($this->functions[$template_func_name]))
        {
            unset($this->functions[$template_func_name]);
        }

    return TRUE;
    }


    // Override MrSingleton method and pass in our class name string
    public static function getInstance($classname = __CLASS__)
    {
        return parent::getInstance($classname);
    }


// end class   
}


?>
