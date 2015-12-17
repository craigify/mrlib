<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Routing Mechanism, or Handler object.  This class is responsible for matching, authenticating and executing your
// route handler/callback functions.  It provides just enough basic functionality to implement a set of HTTP routes
// so you can implement various web services as you see fit.
//

mrlib::load("routes/MrRouteHandlerAuth");
mrlib::load("routes/MrRoute");

abstract class MrRouteHandler extends MrRouteHandlerAuth
{
   protected $autoDisplay;
   private $inputProxy;   // Use MrRoute->inputProxy
   private $outputProxy;  // Use MrRoute->outputProxy
   private $routes;


  /* Constructor
   */

   function __construct()
   {
      $this->autoDisplay = FALSE;
      $this->inputProxy = NULL;
      $this->outputProxy = NULL;
      $this->routes = array();
   }

   
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   // Public interface
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   

   // @return boolean TRUE if successful, otherwise FALSE if there was a problem with the execution.
   public function execute()
   {	  
      // Attempt to match a route based on our route rules.  If no match, throw a user error and tell
      // the HTTP client not found via a 404 status.
      if (!$route = $this->matchRouteFromURI())
      {
         $concreteClassName = get_class($this);
         $uri = $this->getURI();
         trigger_error("{$concreteClassName}: No route for: {$uri}", E_USER_NOTICE);
         $route->outputProxy->setHttpResponseCode(404);
         $route->outputProxy->displayOutput();
         return FALSE;         
      }
      
      // Perform a sanity check on the callback function just to make sure it's really there.
      if (!$this->verifyCallback($route->callback))
      {
         $route->outputProxy->setHttpResponseCode(500);
         $route->outputProxy->displayOutput();
         return FALSE;
      }
         
      // Instruct the route's input proxy object to load request data based on what kind of HTTP request type was used.
      $route->inputProxy->loadInputData($route->requestType);
      
      // If Auto Auth is on, attempt to parse the authentication rules.  
      if ($route->autoAuth === TRUE)
      {
         if (!$isAuthenticated = $this->authenticate($route))
         {
            $route->outputProxy->setHttpResponseCode(403);
            $route->outputProxy->displayOutput();
            return FALSE;            
         }
      }
      
      $this->executeCallback($route);
      
      if ($this->autoDisplay === TRUE)
      {
         $route->outputProxy->displayOutput();
      }
      
      return TRUE;
   }
   
   
   
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   // Methods for contrete classes extending this abstract class
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



   // Set the auto display feature to TRUE or FALSE.  TRUE is the default behavior, which will dump
   // the contents of the output buffer when the event method is executed().
   protected function setAutoDisplay($value)
   {
      $this->autoDisplay = $value;
   }


   // Create a new MrRoute object, populate it with the basic information, and add it to the internal routes array for later
   // processing/matching, etc... Return the new MrRoute object.  You can then set any propery on this route object as you
   // need directly since all properties are public for convenience.
   //
   // @param string $requestType The HTTP request type: POST, GET, etc...
   // @param string $uri The URI pattern to match
   // @param string $callback The name of the method to call in your concrete class when the route is matched
   // @return MrRoute Returns the new MrRoute object representing the route data you just parsed.
   protected function createRoute($requestType, $uri, $callback)
   {
	  $route = new MrRoute();
	  $route->requestType = $requestType;
	  $route->uri = $uri;
	  $route->callback = $callback;
      
      // Now set the default values and object references in the new object.
	  $route->autoAuth = $this->autoAuth;
	  $route->authHandler = $this->authHandler;
	  $route->inputProxy = $this->inputProxy;
	  $route->outputProxy = $this->outputProxy;
      $this->routes[] = $route;
	  return $route;
   }

   
   // Add an existing, pre-populated MrRoute object to the internal route array.  This way you can create your own MrRoute object,
   // and set all properties, then add it.
   //
   // Note: you need to set ALL properties on MrRoute for this to work correctly.  createRoute() does this for you, then returns
   // that object in case you need to modify anything on it.
   //
   // @param MrRoute $route A MrRoute object fully populated and ready to be used.
   // @return boolean Returns TRUE on success, FALSE on failure.
   protected function addRoute($route)
   {
	  if (get_class($route) != "MrRoute")
	  {
		 return FALSE;
	  }
	  
	  $this->routes[] = $route;
	  return TRUE;
   }
   
   
   // Return an array of route rules currently defined.
   // @return array Array of MrRoute objects
   protected function getRoutes()
   {
      return $this->routes;
   }
   



   protected function getURI()
   {      
      if (isset($_SERVER['REQUEST_URI']))
      {
         return $_SERVER['REQUEST_URI'];
      }      
   }



   // Return any matches from the current matched route rule.  If no matches, return empty array.
   // @return array
   protected function getRouteRegexMatches()
   {
      // Remove any prefixed slash in uri string
      $uri = $this->getURI();
      if ($uri[0] == "/") $uri = substr($uri, 1, strlen($uri) - 1);
 
      foreach ($this->routes as $route)
      {  
         $pattern = "[" . $route->uri . "\$]";
         
         if (preg_match($pattern, $uri, $matches) == 1)
         {
            array_shift($matches);
            return $matches;
         }
      }
      
      return array();
   }

   
   
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   // Convenience methods
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   
   // Set the default input proxy to use.  This is a convenience method.  You can create one input proxy object in your handler,
   // set a reference to it using this method, then every createRoute() call will use your default input proxy.
   // @param $obj MrInputProxy An object derived from MrInputProxy
   protected function setDefaultInputProxy($obj)
   {
      $this->inputProxy = $obj;
   }


   // Set the default output proxy to use.  This is a convenience method.  You can create one output proxy object in your handler,
   // set a reference to it using this method, then every createRoute() call will use your default output proxy.
   // @param $obj MrOutputProxy An object derived from MrOutputProxy
   protected function setDefaultOutputProxy($obj)
   {
      $this->outputProxy = $obj;
   }

   
   
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   // Internal methods
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   
   
   // Do some quick sanity checks on the callback method and do a few sanity checks on the callback function
   // name.  If any of these checks fail, we consider this an internal error.  Set the HTTP response code to
   // 500 and return no output hopefully. Trigger a PHP warning error.
   //
   // @param array $route Matched route from the internal route array, returned from matchRouteFromURI()
   // @return boolean Return TRUE if the callback function name is set and is a real function in the concrete object, or FALSE on error.
   private function verifyCallback($callback)
   {
      if (empty($callback))
      {
         $concreteClassName = get_class($this);
         trigger_error("{$concreteClassName}: {$route['uri']}: Matched URI, but there was not a callback specified", E_USER_WARNING);
         return FALSE;         
      }
      
      // Make sure callback is really a method in the concrete object
      if (!method_exists($this, $callback))
      {
         $concreteClassName = get_class($this);
         $uri = $this->getURI();
         trigger_error("{$concreteClassName}: {$uri}: Callback '{$callback}' is not an actual method in the handler", E_USER_WARNING);
         return FALSE;
      }
      
      return TRUE;
   }


   // Attempt to execute the callback function on the concrete object.  We don't really do anything
   // with the return value from that function.  If you want to automatically display the contents
   // of the outputproxy buffer, make sure that the auto display setting is TRUE (which is the default),
   // and execute() will instruct the proxy to output its contents after the callback is completed.
   // @param MrRoute $route The MrRoute object to use
   private function executeCallback($route)
   {      
      call_user_func_array(array($this, $route->callback), array($route));
   }
   
   
   // Attempt to match a routing rule from the URL.  If so, return the array of data.  Otherwise false.
   //
   // Routing rules are an exact match.  getURI() will return a URI with no preceeding slash,
   // so keep that in mind when writing your rules.
   //
   // @return array|boolean Returns matched route array, or FALSE on error.
   private function matchRouteFromURI()
   {
      $uri = $this->getURI();
      
      foreach ($this->routes as $route)
      {
         $pattern = "[" . $route->uri . "\$]";
         
         if (preg_match($pattern, $uri) == 1)
         {
            return $route;
         }
         
         //if (strcasecmp($uri, $route['uri']) == 0)
         //{
         //    return $route;
         //}
      }

      return FALSE;
   }

   
   // Attempt to match a routing rule from the specified URI string.
   // @param $uri string URI string to match.
   // @return array|boolean Returns matched route array, or FALSE on error.
   private function matchRoute($uri)
   {
      // Remove any prefixed slash in uri string
      if ($uri[0] == "/") $uri = substr($uri, 1, strlen($uri) - 1);
 
      foreach ($this->routes as $route)
      {
         $pattern = "[" . $route->uri . "\$]";
         
         if (preg_match($pattern, $uri) == 1)
         {
            return $route;
         }
         
         //if (strcasecmp($uri, $route['uri']) == 0)
         //{
         //   return $route;
         //}
      }

      return FALSE;      
   }


// end class
}


?>
