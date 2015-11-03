<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Mister Template - A lightweight template parsing system with theme support.
//
// Usage:
// ------------------------------------------------------------------------------
//  $template = mrlib::getNew("template/MrTemplate");
//
//  You assign variables one by one:
//     $template->assign("name", "Craig");
//
//  Or in bulk:
//     $vars['name'] = "Craig";
//     $vars['status'] = "active";
//     $template->assign($vars);
//
// Theme support (just subdirectories inside main template directory):
//     $template->setTheme() and $template->getTheme()
//
//  Parse a template and return parsed contents:
//     $data = $template->parse();
//

define("TEMPLATE_DISPLAY",    0);
define("TEMPLATE_RETURN",     1);


class MrTemplate
{
   private $cache;
   private $vars;
   private $theme;

   
   function __construct()
   {
      $this->vars = array();
      $this->theme = FALSE;
   }
   

  
   // Assign template variables.  You can assign variable using two methods:
   //
   // 1. Pass a key, value pair:
   //      assign("name", "Craig")
   //
   // 2. Pass an associative array of values:
   //      $tpl['name'] = "Craig"
   //      $tpl['level'] = "Cool";
   //      assign($tpl);
   //
   public function assign($key, $value="")
   {
      if (is_array($key))
      {
         foreach ($key as $theKey => $theValue)
         {
            $this->vars[$theKey] = $theValue;
         }
      }
      else
      {
         $this->vars[$key] = $value;   
      }
   }
   
   
   // Tell the template parser to use a theme.  All this really means is that it looks for files in a subdirectory inside the templates directory.
   // $theme (string)  Name of theme.  Make it a valid directory name.
   public function setTheme($theme)
   {
      $this->theme = $theme;
   }
   
   
   // Get the current theme identifier string.  If there is no theme set, returns FALSE.
   // @return (String|Boolean)
   public function getTheme()
   {
      return $this->theme;
   }
   

   // Parse a template and return its contents.
   // @return string
   public function parse($filename)
   {
      return $this->parseTemplate($filename);
   }
      
   

   // Clear template variables.  Specify a key to clear a specific template variable.
   // @params string $key
   public function clear($key="")
   {
      if (empty($key))
      {
         $this->vars = array();
      }
      else
      {
         if (isset($this->vars[$key]))
         {
            unset($this->vars[$key]);
         }
      }
   }
   


   // Alias to clear()
   public function clearConfig($key="")
   {
      $this->clear($key);      
   }
   
   
   
   /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   // LEGACY METHODS (DEPRECATED)
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   
   
   // Equivalent method to the Webtemplate template() function.   You can use this to pass the template name and variables in one swoop.
   // If $method is passed constant TEMPLATE_RETURN, return the parsed template data.
   // If $method is passed constant TEMPLATE_DISPLAY, display parsed template data.
   //
   public function template($filename, $vars=array(), $method=TEMPLATE_DISPLAY)
   {
      $parsed = $this->doParseTemplate($this->readFile($filename), $vars);
      
      if ($method == TEMPLATE_RETURN)
         return $parsed;
      else
         echo $parsed;
   }
   
   
   // Parse a template and echo the result
   public function display($filename)
   {
      echo $this->parseTemplate($filename);      
   }
   
   
   
   
   /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   // INTERNAL STUFF
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



   // Parse template data and return it.
   // @params string
   private function parseTemplate($filename)
   {
      return $this->doParseTemplate($this->readFile($filename), $this->vars);
   }
   
   
   
   // Handle the mechanics of template parsing.  This method uses recursion.
   // @return string
   private function doParseTemplate($data, $vars, $matches=array())
   {
      $funcMan = mrlib::getSingleton("template/MrFunctionManager");

      if (empty($matches))
      {
         preg_match_all('/{\$(.+?)}/', $data, $matches);
      }

      // Change key case of all vars to upper for consistency.  This must be done after template function parsing
      // to keep any arguments to template functions in their original case.
      $vars = array_change_key_case($vars, CASE_UPPER);   
      
      // matches[1] contains all of the {$VARS} found inside the template.  The following takes the vars, makes sure
      // they are all uppercase, and places them into templateVars array in the following format:
      //     templateVars[templateVar] = indexNum;
      // We only care about templateVar, so indexNum is just disregarded.
      $templateVars = array_change_key_case(array_flip($matches[1]), CASE_UPPER);
      
      foreach ($templateVars as $key => $index)
      {
         /* Parse and call any template functions */
         if (preg_match("/%(.*?)%/", $key, $command))
         {
            array_walk($command, "mr_template_trim_argument_string");

            $segments = explode(" ", $command[1], 2);  // segments[0] contains function name, segments[1] contains argument string
            array_walk($segments, "mr_template_trim_argument_string");

            if (isset($segments[1]))
            {
               // Split arguments to the template function treating them like CSV.   eg. First, Second Argument, "Third Argument, with a comma"
               $args = preg_split("/,(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/", $segments[1]);
               array_walk($args, "mr_template_trim_argument_string", ' "');
            }
            else
            {
               $args = array();
            }

            $func = strtoupper($segments[0]);
            $key = strtoupper($key);

            /* Call func with no args */
            if (empty($args))
            {
               $vars[$key] = $funcMan->callFunction($func);
            }
            else
            {
               $vars[$key] = $funcMan->callFunction($func, $args);
            }
            
         // end template function match check
         }

         // begin template var matching
         if (!isset($vars[$key]))
         {
            $vars[$key] = "";
         }

         /* Parse any template vars in the replacement content using recursion */
         //if (preg_match_all("/{\$(.+?)}/", $vars[$key], $rMatches))
         //{
         //   $vars[$key] = $this->doParseTemplate($vars[$key], $vars, $rMatches);
         //}

         /* Replace template var with replacement content */
         $data = preg_replace('/{\$' . preg_quote($key, "/") . '}/i', $vars[$key], $data);
      }
      
      return $data;
   }



   // Read in a file from disk. Cache files in memory for subsequent calls.
   // @return string
   private function readFile($filename)
   {
      if (empty($this->theme))
      {
         $fullfilename = MRLIB_TEMPLATE_DIR . "/" . $filename;
      }
      else
      {
         $fullfilename = MRLIB_TEMPLATE_DIR . "/" . $this->theme . "/" . $filename;
      }
      
      $cache = mrlib::getSingleton("template/MrTemplateCache");

      if ($cache->hasKey($fullfilename))
      {
         $data = $cache->get($fullfilename);
      }      
      else
      {         
         $data = file_get_contents($fullfilename, TRUE);
         $cache->set($fullfilename, $data);
      }

   return $data;
   } 

   

// end class
}




// Utility function used by array_walk() to trim array values
function mr_template_trim_argument_string(&$value, $key, $chars=" ")
{
   $value = trim($value, $chars);
}



?>
