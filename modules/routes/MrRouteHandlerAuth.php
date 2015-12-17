<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Event Handler Authentication System
//
// Extend the Authentication Framework and provide an authentication system for the event handler.
// This enables you to set authentication policies for entire front controllers, for events, or for specific
// access levels as you see fit.
//

/* Define AUTH_DENY as 0 so that incorrectly typed constants will pass 0, and deny by default */
define("MRLIB_AUTH_DENY",  0);
define("MRLIB_AUTH_ALLOW", 1);


class MrEventAuth
{
   protected $autoAuth;
   protected $authHandler;
   protected $globalAuthPolicy;
   
   protected $authCallbackMethod;
   protected $noAuthCallbackMethod;
   
   protected $eventAuthPolicy = array();
   protected $globalAuthRules = array();
   protected $eventAuthRules = array();


  /* Constructor.
   */

   function __construct()
   {
      $this->defaultAuthPolicy = MRLIB_AUTH_ALLOW;
      $this->authCallbackMethod = "onAuth";
      $this->noAuthCallbackMethod = "onNoAuth";
      $this->autoAuth = FALSE;
      $this->authHandler = FALSE;      
   }


  /* Set auto authentication on/off.  If turned on, authenticate() is called automatically by the execute()
   * method to check for authentication credentials.   By default this is turned off.
   *
   * @param $toggle (boolean)  Specify TRUE to turn this feature on, FALSE to leave it off (default)
   */

   public function setAutoAuth($toggle)
   {
      switch ($toggle)
      {
         case TRUE:
           $this->autoAuth = TRUE;
         break;

         case FALSE:
           $this->autoAuth = FALSE;
         break;
      }
   }



  /* Explictly specify an Authentication Handler to use by passing in a reference to one.  This should only
   * be used when necessary when the MrAuthManager cannot be used for some reason.
   * 
   * @param $ref (MrAuthHandler)  An initialized object derived from MrAuthHandler
   */

   public function setAuthHandler($ref)
   {
      $this->authHandler = $ref;
   }


  /* Set the default auth policy for the event handler.  Normally this is set to allow everything, but
   * it can be set to deny everything by default.  You can then set specific rules to allow access.
   *
   * @param $policy (constant)  Specify a auth policy constant of MRLIB_AUTH_ALLOW or MRLIB_AUTH_DENY
   * @return (boolean)  Will return FALSE if policy constant is not valid, otherwise returns TRUE
   */

   public function setDefaultAuthPolicy($policy)
   {
      if (!$this->validateAuthPolicy($policy))
      {
         return FALSE;
      }
      else
      {
         $this->defaultAuthPolicy = $policy;
      }

   return TRUE;
   }


  /* Set the default auth policy for a specific event.  This means that if no matching auth rules are
   * met, this policy is the fallback for the event.  For the entire event handler, use SetDefaultAuthPolicy()
   *
   * @param $eventName (string)  The name of the event to bind the default policy.
   * @param $policy (constant)   A valid auth policy constant of MRLIB_AUTH_ALLOW or MRLIB_AUTH_DENY
   * @return (boolean)  Will return FALSE if policy constant is not valid, otherwise returns TRUE
   */

   public function setDefaultEventAuthPolicy($eventName, $policy)
   {
      if (!$this->validateAuthPolicy($policy))
      {
         return FALSE;
      }
      else
      {
         $this->eventAuthPolicy[$eventName] = $policy;
      }

   return TRUE;
   }


  /* Add a default authentication rule to the entire event handler, regardless of event.  Note that any event
   * authentication rules are processed first, and can override the default rule.
   *
   * The following would allow a user with the "ADMIN" ACL to be allowed to execute ANY event:
   *   addDefaultAuthRule("ADMIN", MRLIB_AUTH_ALLOW)
   *   
   * @param $authLevelIdentifier (string)  An auth level identifier that is defined in the MrAuthLevel db table
   * @param $policy (constant)   A valid auth policy constant of MRLIB_AUTH_ALLOW or MRLIB_AUTH_DENY
   * @return (boolean)  Will return FALSE if policy constant is not valid, otherwise returns TRUE
   */

   public function addDefaultAuthRule($authLevelIdentifier, $policy)
   {
      $authLevelIdentifier = strtoupper($authLevelIdentifier);

      if (!$this->validateAuthPolicy($policy))
      {
         return FALSE;
      }

      $new = array();
      $new['identifier'] = $authLevelIdentifier;
      $new['policy'] = $policy;
      $this->globalAuthRules[] = $new;

   return TRUE;
   }



  /* Add an event auth rule that applies only to the specified event.  Event rules are processed first.
   *
   * Blanket deny, allow rules:
   *   addEventAuthRule("MyEvent",   MRLIB_AUTH_ALLOW)   would allow open access to event "MyEvent".  Don't even require a valid login.
   *   addEventAuthRule("YourEvent", MRLIB_AUTH_DENY)    would deny access to "YourEvent" no matter what, period, ever.  Probably not too useful really, but it is here.
   *
   * Specific rules:
   *   addEventAuthRule("MyEvent",   MRLIB_AUTH_ALLOW, "ADMIN")   would allow access to event "MyEvent" if you have the "ADMIN" level.
   *   addEventAuthRule("YourEvent", MRLIB_AUTH_DENY,  "ADMIN")   would deny access to "YourEvent" if you have the "ADMIN" level.
   *
   * If you need to DENY or ALLOW by default, set that in the default rules.
   * 
   * @param $eventName (string)  The name of the event to bind the default policy.
   * @param $policy (constant)   A valid auth policy constant of MRLIB_AUTH_ALLOW or MRLIB_AUTH_DENY
   * @param $authLevelIdentifier (string)  An auth level identifier that is defined in the MrAuthLevel db table
   * @return (boolean)  Will return FALSE if policy constant is not valid, otherwise returns TRUE
   */

   public function addEventAuthRule($eventName, $policy, $authLevelIdentifier=NULL)
   {
      //if ($authLevelIdentifier != NULL) $authLevelIdentifier = strtoupper($authLevelIdentifier);
      //$eventName = strtoupper($eventName);

      if (!$this->validateAuthPolicy($policy))
      {
         trigger_error("Invalid auth policy: {$policy}", E_USER_WARNING);
         return FALSE;
      }

      $new = array();
      $new['identifier'] = $authLevelIdentifier;
      $new['policy'] = $policy;
      $this->eventAuthRules[$eventName][] = $new;

   return TRUE;
   }


  /* Check authentication credentials for by inspecting the configured definitions in the following order:
   * 1. event auth rules.
   * 2. default event auth policy.
   * 
   * If a match is found, processing stops immediately and returns the authentication result right away. If
   * NO match is found, the default auth policy is returned.
   *
   * @return (bool) TRUE if sufficient authentication credentials are met, FALSE if not.
   */

   public function authenticate()
   {
      $onAuthFunc = $this->authCallbackMethod;
      $onNoAuthFunc = $this->noAuthCallbackMethod;
            
      if ($this->doAuthenticate())
      {
         if (method_exists($this->controllerObj, $onAuthFunc))
         {
            call_user_func(array($this->controllerObj, $onAuthFunc));            
         }
         return TRUE;
      }
      else
      {
         if (method_exists($this->controllerObj, $onNoAuthFunc))
         {
            call_user_func(array($this->controllerObj, $onNoAuthFunc));
         }
         return FALSE;
      }
   }


   // Perform the authentication and return TRUE/FALSE
   private function doAuthenticate()
   {
      $eventName = $this->eventName;
   
      // Get a reference to the authentication handler if we don't yet have it.
      if (!$this->authHandler)
      {
         $this->authHandler = mrlib::getSingleton("auth/MrAuthManager")->getAuthHandler();
      }

      // If not logged in, there is one special case that we need to check for:  Make sure there are not event
      // auth rules that allow a certain event to be accessed with an allow rule and no identifier.  This means
      // that there is no authentication requirement for that event; not even a valid login session.
      if (!$this->authHandler->isLoggedIn())
      {
         if (isset($this->eventAuthRules[$eventName]))
         {
            foreach ($this->eventAuthRules[$eventName] as $index => $rule)
            {
               if ($rule['identifier'] == NULL && $rule['policy'] == MRLIB_AUTH_DENY) return FALSE;
               if ($rule['identifier'] == NULL && $rule['policy'] == MRLIB_AUTH_ALLOW) return TRUE;
            }
         }
         
         // If no open access event rules are present, don't grant access.
         return FALSE;
      }

     /* We look for one of the following situations to happen, in this order:
      *  1. If any auth rule matches MRLIB_AUTH_ALLOW, Pass.
      *  2. If any auth rule matches MRLIB_AUTH_DENY, Fail.
      *  3. Event Auth Policy is set to MRLIB_AUTH_ALLOW and no event auth rule explicitly matches.  Pass.
      *  4. Event Auth Policy is set to MRLIB_AUTH_DENY and no event auth rule explicitly matches.  Fail.
      *  5. Default Auth Policy is set to MRLIB_AUTH_ALLOW and no event auth rule explicitly matches.  Pass.
      *  6. Default Auth Policy is set to MRLIB_AUTH_DENY and no event auth rule explicitly matches.  Fail.
      *  7. No rules, event or global policy is defined.  Fail by default.
      *
      * In order words, auth rules have precedence.  Next is default event policy, then default policy.
      */

      $eventCheckResult = $this->checkEventAuthRules();

      if ($eventCheckResult === TRUE)
      {
         return TRUE;
      }

      if ($eventCheckResult === FALSE)
      {
         return FALSE;
      }

      if (isset($this->eventAuthPolicy[$eventName]) && $this->eventAuthPolicy[$eventName] == MRLIB_AUTH_ALLOW)
      {
         return TRUE;
      }

      if (isset($this->eventAuthPolicy[$eventName]) && $this->eventAuthPolicy[$eventName] == MRLIB_AUTH_DENY)
      {
         return FALSE;
      }

      $globalCheckResult = $this->checkGlobalAuthRules();

      if ($globalCheckResult === TRUE)
      {
         return TRUE;
      }

      if ($globalCheckResult === FALSE)
      {
         return FALSE;
      }

      if ($this->defaultAuthPolicy == MRLIB_AUTH_ALLOW)
      {
         return TRUE;
      }

      return FALSE;
   }



  /* Check event auth rules. Get access levels and see if we get an explicit match.  If we find an explicit
   * match, return TRUE for allow and FALSE for deny.  Once we find the first match, we return and ignore any
   * possible subsequent matches.
   *
   * It is possible that this returns NEITHER TRUE OR FALSE.  This means that we went through the rules
   * and nothing explicitly matched.
   */

   private function checkEventAuthRules()
   {
      $levels = $this->authHandler->getAuthUser()->getAuthLevels();
      $eventName = strtoupper($this->eventName);

      foreach ($levels as $identifier => $detail)
      {
         if (isset($this->eventAuthRules[$eventName]))
         {            
            foreach ($this->eventAuthRules[$eventName] as $index => $rule)
            {
               if ($rule['identifier'] == $identifier && $rule['policy'] == MRLIB_AUTH_DENY) return FALSE;
               if ($rule['identifier'] == $identifier && $rule['policy'] == MRLIB_AUTH_ALLOW) return TRUE;
            }
         }
      }
   }



  /* Same as above.  Check global auth rules.
   */

   private function checkGlobalAuthRules()
   {
      $levels = $this->authHandler->getAuthUser()->getAuthLevels();
      $eventName = strtoupper($this->eventName);

      foreach ($levels as $identifier => $detail)
      {
         foreach ($this->globalAuthRules as $index => $rule)
         {
            if ($rule['identifier'] == $identifier && $rule['policy'] == MRLIB_AUTH_DENY) return FALSE;
            if ($rule['identifier'] == $identifier && $rule['policy'] == MRLIB_AUTH_ALLOW) return TRUE;
         }
      }
   }



  /* Internal method to validate auth policy.
   */

   private function validateAuthPolicy($policy)
   {
      switch ($policy)
      {
         case MRLIB_AUTH_ALLOW:
            return TRUE;
         break;

         case MRLIB_AUTH_DENY:
            return TRUE;
         break;

         default:
            return FALSE;
         break;
      }
   }



/* end EventAuth class */
}

?>