<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Default Authentication Handler - This class manages the login session, and provides methods
// for the authentication and de-authentication of users.
//
// Rules for extending this class:
// --------------------------------------------------------------------------------------------
// * This class can be extended to provide authentication against external sources.  You can
//   override whatever methods that you need.  Primarily you'll need to override login().
//
// * If you use an external authentication source, make sure that you create a local user when
//   you successfully authenticate against that source.  To keep in sync, you can update the
//   local user every time login() is called.
//
// * Make sure your login() method sets this->MrAuthUser and you call this->commit();
//
// * You need to tell the MrAuthManager to use your extended class, and tell it before you start
//   doing things in your app, especially with mrlib.
//
//   One such way is to auto include a file with this in it:
//   mrlib::getSingleton("auth/MrAuthManager")->setAuthHandler(new MyExtendedAuthHandler());
//

mrlib::load("auth/MrAuthUser");
mrlib::load("auth/MrAuthGroup");

class MrAuthHandler
{
   protected $sessionData;
   protected $MrAuthUser;


   // Constructor.
   function __construct()
   {
      $this->initializeSession();
   }


   // Get the current login session data.  This returns an associative array containing information about the current
   // authenticated user and an array of acces levels in key=>value format.  If there is currently no active login
   // session, this will return an empty array.
   //
   // @return (array) Login session data.
   public function getLoginSession()
   {
      if (empty($this->sessionData))
      {
         if (isset($_SESSION['mrlib_auth_session']) && isset($_SESSION['mrlib_auth_session']['user']) && isset($_SESSION['mrlib_auth_session']['user']['id']))
         {
            $this->sessionData = $_SESSION['mrlib_auth_session'];         
         }
         else
         {
            $this->sessionData = array();
         }
      }
      
      return $this->sessionData;
   }


   // Attempt to authenticate a user by specifying a username string and password string (unencrypted of course) and
   // start a new login session. If there is a previous login session, this will completely invalidate it, even if
   // the current call to login() was unsuccessful.
   //
   // @param (string)  Username of user to authenticate
   // @param (string)  Password ot user to authenticate
   // @return (bool)   Return TRUE if user was authenticated, FALSE otherwise.
   public function login($username, $password)
   {
      $this->invalidateLoginSession();
      $MrAuthUser = new MrAuthUser();
      $MrAuthUser->loadByUsername($username);    
      $validPass = $MrAuthUser->validatePassword($password);
      
      if ($validPass === true)
      {
         $MrAuthUser->loadAuthLevels();
         $this->MrAuthUser = $MrAuthUser;
         $this->commit();
         return TRUE;
      }

   return FALSE;
   }


   // Check if there is a current login session.
   // @return (bool)  TRUE if there is a valid login session, otherwise return FALSE.
   public function isLoggedIn()
   {
      if (isset($this->sessionData['user']) && isset($this->sessionData['user']['id']) && !empty($this->sessionData['user']['id']))
      {
         return TRUE;
      }
      else
      {
         return FALSE;
      }
   }
   
   
   // Alias to isLoggedIn()
   // @return (bool)  TRUE if there is a valid login session, otherwise return FALSE.
   public function isAuthenticated()
   {
      return $this->isLoggedIn();
   }


   // Return a fully loaded/synched MrAuthUser ORM object.  The object is cached in this handler class, so subsequent
   // calls to this method will not trigger another set of database queries.  There must be a current, valid login
   // session for this to work.  Return FALSE if no login session, or error.
   //
   // @return (MrAuthUser)  A MrAuthUser object represting the currently logged in user, or FALSE.
   public function getAuthUser()
   {
      if ($this->isLoggedIn())
      {
         if ($this->MrAuthUser->isSynched())
         {
            return $this->MrAuthUser;
         }
         else
         {
            $this->MrAuthUser->load($this->sessionData['user']['id']);

            if ($this->MrAuthUser->isSynched())
            {
               $this->MrAuthUser->loadAuthLevels();
               return $this->MrAuthUser;
            }
            else
            {
               // For some reason the ORM didn't properly load the database record.
               return FALSE;
            }
         }
      }
      
      // No valid login session.
      else
      {
         return FALSE;
      }
      
      // Shouldn't get here, but if you do (somehow) ...
      return FALSE;
   }


   // Log out the current user.
   public function logout()
   {
      $this->invalidateLoginSession();
   }


   // Commit the login data to the session.
   protected function commit()
   {
      $_SESSION['mrlib_auth_session'] = array();  // Just in case?
      $_SESSION['mrlib_auth_session']['user'] = $this->MrAuthUser->getFields();
      $_SESSION['mrlib_auth_session']['authLevels'] = $this->MrAuthUser->authLevels;
      $this->sessionData = $_SESSION['mrlib_auth_session'];
   }
   
   
   // Invalidate the current login session.   
   protected function invalidateLoginSession()
   {
      $_SESSION['mrlib_auth_session'] = array();
      $this->sessionData = array();
   }



   /////////////////////////////////////////////////////////////////////////////
   // Some useful utility methods for extending this class
   /////////////////////////////////////////////////////////////////////////////


   // Check if a user with the unique username exists in the database.
   // @param (string)  The username to check,
   // @retrun (bool)   TRUE if the username exists, FALSE if it does not.
   function checkIfUserExists($username)
   {
      $writer = mrlib::getSingleton("sql/MrDatabaseManager")->getWriter();

      // Easier just to make a query here...
      $username = $writer->e($username);
      $query = "SELECT COUNT(auth_id) AS num FROM MrAuthUser WHERE auth_username = '{$username}'";
      $num = $writer->queryFetchItem($query, "num");
      
      if ($num)
      {
         return TRUE;
      }
      else
      {
         return FALSE;
      }
   }


   // Initialize the authentication session. Normally this is done automatically when you instantiate this class.
   // Calling this method again will cleanly re-initialize the auth handler and delete any previous memory of a
   // login session.
   //
   // 99% OF THE TIME YOU WILL NEVER NEED TO USE THIS.
   //
   public function initializeSession()
   {
      $this->sessionData = array();
      $this->MrAuthUser = new MrAuthUser();
      $sid = session_id();

      if (empty($sid))
      {
         session_start();
      }
      
      $this->getLoginSession();
   }
   
   
   // Override MrSingleton method and pass in our class name string
   //public static function getInstance($classname = __CLASS__)
   //{
   //   return parent::getInstance($classname);
   //}


/* end AuthHandler class */
}



?>
