<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Default Authentication Handler
//
// All Authentication Handlers must implement MrAuthHandlerInterface.  This one gets used as the default handler by
// the MrRouteRequest mediator unless you write one and tell it to use yours.
//
// You can write your own authentication handler and use any authentication source you can dream up, as long as it
// implements MrAuthHandlerInterface and mangles the data in a way that is expected.  You then use the MrAuthManager
// singleton to use your auth handler.
//

mrlib::load("auth/MrAuthSession");
mrlib::load("auth/interfaces/MrAuthHandlerInterface");


class MrAuthHandler implements MrAuthHandlerInterface
{
   protected $authSession;


   // Constructor.
   function __construct()
   {
	  $this->authSession = new MrAuthSession();
   }


   // Attempt to authenticate a user by specifying a username string and password string (unencrypted of course) and
   // start a new login session. If there is a previous login session, this will completely invalidate it, even if
   // the current call to login() was unsuccessful.
   //
   // @param string $username Username of user to authenticate
   // @param string $password Password ot user to authenticate
   // @return boolean Return TRUE if user was authenticated, FALSE otherwise.
   public function login($username, $password)
   {
      $this->authSession->invalidateSession();
      $user = new MrAuthUser();
      $user->loadByUsername($username);    
      $validPass = $user->validatePassword($password);
      
      if ($validPass === true)
      {
         $user->loadAuthLevels();
         $this->authSession->setAuthUser($user);
         $this->authSession->saveSession();
         return TRUE;
      }

   return FALSE;
   }


   // Log out the current user.
   public function logout()
   {
      $this->authSession->invalidateSession();
   }
   
   
   // Check if there is a valid auth session
   // @param MrRoute $route The current matched MrRoute object
   // @return boolean TRUE if there is a valid login session, otherwise return FALSE.
   public function loadAuthSession($route)
   {
	  return ($this->authSession->isValid());
   }
   

   // Check if a user with the unique username exists in the database.
   // @param string $username The username to check,
   // @retrun boolean TRUE if the username exists, FALSE if it does not.
   public function checkIfUserExists($username)
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
   
   
   // Get a MrAuthUser object that represents the current authenticated user.
   // @return MrAuthUserInterface An object that implements MrAuthUserInterface, represting the currently logged in user, or FALSE.
   public function getAuthUser()
   {
      if (!$this->authSession->loadAuthSession())
      {
         return FALSE;
      }
	  
	  if (!$this->authSession->isAuthenticated())
      {
         return FALSE;
      }      
	  
	  return $this->authSession->getAuthUser();
   }
   

   // @return MrAuthSession Gets areference to the MrAuthSession object.
   public function getAuthSession()
   {	  
	  return $this->authSession();
   }


// end class
}


?>
