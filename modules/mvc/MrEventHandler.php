<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Event Handling and Routing Layer.  This class allows you to define routing rules,
// and tie in events to controllers based on input data criteria.  It also ties in
// the Authentication layer so you can configure ACL rules.
//
// Usage:
// ----------------------------------------------------------------------------------------
// 1) First tell the handler what input data to use, and what variable is the event
//    variable.  Event variable is used to tell the handler which method to use if not
//    explicitly defined in a route.
//
// 2) Set the default controller to be used if no routes are defined or matched.
//
// 3) Set the default event.  This is executed if the event variable is not in the specifed
//    input data.
//
// 4) You can then map URI strings to Controllers and Controllers+events.
//
// 5) Configure any potential ACL rule combinations.  See MrAuthHandler.php
//
// 6) Call the execute() method.
//

mrlib::load("mvc", "MrEventAuth.php");
mrlib::load("mvc", "MrEventValidator.php");
mrlib::load("mvc", "MrController.php");

class MrEventHandler extends MrEventValidator
{
   public $routes;
   protected $controllerObj;
   protected $inputProxy;
   protected $outputProxy;
   protected $eventName;
   protected $defaultEvent;
   protected $autoDisplay;
   protected $errorType;


  /* Constructor
   */

   function __construct($controllerName="")
   {      
      parent::__construct();
      $this->defaultEvent = FALSE;       // important
      $this->eventName = FALSE;          // important
      $this->autoDisplay = TRUE;
      $this->inputProxy = NULL;
      $this->outputProxy = NULL;
      $this->routes = array();
   }

   
   // Set the auto display feature to TRUE or FALSE.  TRUE is the default behavior, which
   // will dump the contents of the output buffer when the event method is executed().
   public function setAutoDisplay($value)
   {
      $this->autoDisplay = $value;
   }


   // Add a route rule to the event handler
   public function addRoute($uri, $controllerName, $defaultEvent=FALSE)
   {
      $route = array();
      $route['uri'] = $uri;
      $route['controllerName'] = $controllerName;
      $route['defaultEvent'] = $defaultEvent; // keep this 'defaultEvent' 
      $this->routes[] = $route;
   }

   
   // Return an array of route rules currently defined.
   public function getRoutes()
   {
      return $this->routes;
   }
   

   // Set the default controller object by passing a reference to your controller object.  If
   // no routes match, then this controller is used.  Also see setDefaultEvent()
   // @param $obj (MrController)   An object derived from MrController
   public function setDefaultController($obj)
   {
      $this->controllerObj = $obj;
      $this->controllerObj->setEventHandler($this);
      
   }

   // Alias to setDefaultController()   
   public function setController($obj)
   {
      $this->setDefaultController($obj);
   }


   // Specify the default event to execute if no event variable is set.
   public function setDefaultEvent($eventName)
   {
      if (empty($eventName))
      {
         return FALSE;
      }

      $this->defaultEvent = $eventName;

   return $eventName;
   }


  /* Set the event handler variable that will contain the event to execute in the controller.
   * setEventHandler("POST", "m") would tell the the event handler to look for an event name
   * inside of $_POST['m'].
   *
   * Note: Routes are processed first, then the event variable.  If no match is found, the
   * default event is executed on the default controller.  If that is not set, just give up! :)
   *
   * @param $type (string)  Can be 'POST', 'GET', or 'REQUEST'
   * @param $var  (string)  Variable in the input array that contains the event name.
   */

   public function setEventHandler($type, $var)
   {
      $this->getInputProxy();
      $this->inputProxy->addInputData($type);

      // Get event name
      if ($this->inputProxy->isDefinedNotEmpty($var))
      {
         $this->eventName = $this->inputProxy->getVar($var);
      }
   }


   // IMPORTANT NOTE ABOUT THIS METHOD!
   //
   // This method should not be used directly, except in edge cases.  Use the MrProxyManager object
   // to set your Input Proxy.
   //
   // @param $obj (MrOutputProxy)  An output object derived from MrInputProxy
   public function setInputProxy($obj)
   {
      if (!$this->inputProxy)
      {
         $this->inputProxy = $obj;
      }
   }


   // IMPORTANT NOTE ABOUT THIS METHOD!
   //
   // This method should not be used directly, except in edge cases.  Use the MrProxyManager object
   // to set your Output Proxy.
   //
   // @param $obj (MrOutputProxy)  An output object derived from MrOutputProxy
   public function setOutputProxy($obj)
   {
      $this->outputProxy = $obj;
   }



   // Return the internal refernece to the input proxy object.
   // @return (MrInputProxy)  Returns an MrInputProxy object or object derived from it.
   public function getInputProxy()
   {
      if (!$this->inputProxy)
      {
         $this->inputProxy = mrlib::getSingleton("proxy/MrProxyManager")->getInputProxy();
      }
      
      return $this->inputProxy;
   }


   // Return the internal refernece to the output proxy object.
   // @return (MrOutputProxy)  Returns an MrOutputProxy object or object derived from it.
   public function getOutputProxy()
   {
      if (!$this->outputProxy)
      {
         $this->outputProxy = mrlib::getSingleton("proxy/MrProxyManager")->getOutputProxy();
      }
      
      return $this->outputProxy;
   }


   // Attempt to match a routing rule from the URL.  If so, return the array of data.  Otherwise false.
   //
   // Routing rules are an exact match.  getURI() will return a URI with no preceeding slash,
   // so keep that in mind when writing your rules.
   //
   // @return (array|boolean)  Returns matched route array, or FALSE on error.
   public function matchRouteFromURI()
   {
      $uri = mrlib::getSingleton("core/MrRouteRequest")->getURI();
      
      foreach ($this->routes as $route)
      {
         $pattern = "[" . $route['uri'] . "]";
         
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
   // @param  (string) $uri    URI string to match.
   // @return (array|boolean)  Returns matched route array, or FALSE on error.
   public function matchRoute($uri)
   {
      // Remove any prefixed slash in uri string
      if ($uri[0] == "/") $uri = substr($uri, 1, strlen($uri) - 1);
 
      foreach ($this->routes as $route)
      {
         $pattern = "[" . $route['uri'] . "]";
         
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


   // Return any matches from the current matched route rule.  If no matches, return empty array.
   public function getRouteRegexMatches()
   {
      // Remove any prefixed slash in uri string
      $uri = mrlib::getSingleton("core/MrRouteRequest")->getURI();
      if ($uri[0] == "/") $uri = substr($uri, 1, strlen($uri) - 1);
 
      foreach ($this->routes as $route)
      {  
         $pattern = "[" . $route['uri'] . "]";
         
         if (preg_match($pattern, $uri, $matches) == 1)
         {
            array_shift($matches);
            return $matches;
         }
      }
      
      return array();
   }


   // Execute the event on the specified controller.  This method first attempts to instantiate the controller object, then
   // call the public method that matches the event name on that object.  If desired, you can specify the controllerName
   // and eventName directly as arguments to this function.
   //
   // @param  (string)  $controllerName   Optional, The name of the controller class to instiantiate.
   // @param  (string)  $eventName        Optional, The event name that matches a method on the controller object.
   // @return (boolean) TRUE if successful, otherwise FALSE if there was a problem with the execution.
   public function execute($controllerName="", $eventName="")
   {
      $executeValidation = TRUE;
      $executeAuth = TRUE;

      // If controller name was specified, attempt to initilize object matching the class name.
      if (!empty($controllerName))
      {
         $this->controllerObj = new $controllerName;         
      }
      
      // If we have a route match, attempt to initialize the specified controller and save
      // the event name, if specified.  If not, we determine event name from the input variable.
      else if ($route = $this->matchRouteFromURI())
      {
         $this->controllerObj = new $route['controllerName'];
      }

      // If event name was specified in function arguments, default to that.
      if (!empty($eventName))
      {
         $this->eventName = $eventName;
      }
      
      // If event name was not specfied in function argument, check to see if event name was determined by setEventHandler() from GET or POST
      // data.  If it was not determined, then attempt to use default event if there is one.
      else if (empty($this->eventName))
      {
         // Use default event for the matching route if we can.
         if (isset($route['defaultEvent']) && !empty($route['defaultEvent']))
         {
            $this->eventName = $route['defaultEvent'];            
         }
         // Or fall back to default.
         else
         {
            $this->eventName = $this->defaultEvent;            
         }
      }

      // Check that the method exists.
      if (!method_exists($this->controllerObj, $this->eventName))
      {
         $controllerName = get_class($this->controllerObj);
         trigger_error("Event '{$this->eventName}' not available on controller '{$controllerName}'.  Giving up, Sorry!", E_USER_WARNING);
         return FALSE;
      }

      // Set a reference to the event handler (us) on the controller object.
      $this->controllerObj->setEventHandler($this);

      // Get the output proxy.
      $this->getOutputProxy();

      // Check authentication credentials.  Execute any callback methods.  See file for details.
      // NOTE that failed authentication will not trigger a FALSE return value from execute()
      if ($this->autoAuth === TRUE)
      {
         $executeAuth = $this->authenticate();
      }

      // Execute event method in controller.
      if ($executeAuth === TRUE)
      {
         $func = $this->eventName;
         $eventResult = $this->controllerObj->{$func}();
      }
      
      // Display output buffer?
      if ($this->autoDisplay === TRUE)
      {
         $this->outputProxy->displayOutput();         
      }      
   }


   // Stop the execution of the event handler, and dump the contents of the output proxy.  Then exit.
   public function stopExecution()
   {
      $this->outputProxy->displayOutput();      
      exit();
   }



/* end EventHandler class */
}




?>
