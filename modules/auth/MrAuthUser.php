<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// This class represents a User.
//
// This class both extends MrORM to provide the database abstraction, and implements MrAuthUserInterface, which describes
// the required implementation for a user in the mrlib auth framework.
//

mrlib::load("sql/MrORM");
mrlib::load("auth/MrAuthUser");
mrlib::load("auth/MrAuthGroup");
mrlib::load("auth/MrAuthLevel");
mrlib::load("auth/interfaces/MrAuthUserInterface");


class MrAuthUser extends MrORM implements MrAuthUserInterface
{
   public $id;
   public $username;
   public $password;
   public $realname;
   public $updated;
   public $created;
   public $deleted;   // "Y" or "N"
   
   // Encryption method.
   public $encMethod = "plain";
   
   // Array of auth levels in key=>value format, where key is the identifier and value is name of level, eg:
   // authLevels['ADMIN'] = "Admin auth Level"
   public $authLevels = array();
   
   // To keep track of them internally.
   public $userAuthLevels = array();
   public $groupAuthLevels = array();

   // Booleans
   public $validUser = false;
   public $validPass = false;
   

   function __construct()
   {
      MrORM::__construct();
      MrORM::addMap("id",            "auth_id");
      MrORM::addMap("username",      "auth_username");
      MrORM::addMap("password",      "auth_password");
      MrORM::addMap("realname",      "auth_realname");
      MrORM::addMap("updated",       "auth_updated");
      MrORM::addMap("created",       "auth_created");
      MrORM::addMap("deleted",       "auth_deleted");
      MrORM::addPrimaryKey("id");
   }
   
   
   // @return boolean TRUE if this object is loaded with a valid user, FALSE is not.
   public function isUserValid()
   {
      return $this->validUser;
   }
   
   
   // @return boolean TRUE if password was validated, FALSE if not.
   public function isPasswordValid()
   {
      return $this->validPass;   
   }
   
   
   // Compare password on record to specified password.
   // @param $password string Plaintext password to verify
   // @return boolean TRUE if password hash matches, FALSE if not
   public function validatePassword($password)
   {
      $encpass = $this->encryptPassword($password);
      
      if ($this->password == $encpass)
      {
         $this->validPass = TRUE;
         return TRUE;
      }
      else
      {
         $this->validPass = FALSE;
         return FALSE;
      }
   }
   
   
   public function getUsername()
   {
      return $this->username;
   }
   
   
   // Retrieve all auth levels for this user. Return an array of auth level identifiers as strings, e.g.
   // array("ADMIN", "GUEST", "API_ACCESS")
   //
   // @return array Auth level array for the user.  Might be empty if no auth levels defined.
   public function getAuthLevels()
   {
      if (empty($this->authLevels))
      {
         $this->loadAuthLevels();
      }

      return array_keys($this->authLevels);
   }


   // Check if this user has a specified auth level.
   // @return boolean TRUE if so, FALSE otherwise.
   public function hasAuthLevel($identifier)
   {
      if (empty($this->authLevels))
      {
         $this->loadAuthLevels();
      }

      foreach ($this->authLevels as $identifier => $name)
      {
         if (strtoupper($identifier) == strtoupper($identifier))
         {
            return TRUE;
         }
      }

   return FALSE;
   }



   // Assign an auth level to the current user.
   // @param $identifier string The auth level identifier string
   // @return TRUE/FALSE
   public function addAuthLevel($identifier)
   {
      $writer = $this->getWriter();
      $level = new MrAuthLevel();
      $level->loadByIdentifier($identifier);

      if (!empty($level->id) && $level->isSynched() == TRUE)
      {
         $insert['auth_id'] = $this->id;
         $insert['auth_level_id'] = $level->id;

         if (!$writer->doInsertIgnore("MrAuthUserLevel", $insert))
         {
            return FALSE;
         }

         $this->authLevels[$identifier] = $level->name;
      }
      // Could not determine if $identifier was valid.
      else
      {
         return FALSE;
      }

   return TRUE;
   }



   // Remove an auth level from this user.
   // @param $identifier (string) The auth level identifier string
   // @return TRUE/FALSE
   public function removeAuthLevel($identifier)
   {
      $writer = $this->getWriter();
      $level = new MrAuthLevel();
      $level->loadByIdentifier($identifier);

      if (!empty($level->id) && $level->isSynched() == TRUE)
      {
         $conditions[] = "auth_id = {$this->id}";
         $conditions[] = "auth_level_id = {$level->id}";
         
         if (!$writer->doDelete("MrAuthUserLevel", $conditions, "LIMIT 1"))
         {
            return FALSE;
         }

         // Be sure to check if the auth level is in the group.  If so, keep it in the authLevels array.
         if ($writer->affectedRows() == 1 && isset($this->authLevels[$identifier]) && !isset($this->groupAuthLevels[$identifier]))
         {
            unset($this->authLevels[$identifier]);
         }
         else
         {
            return FALSE;
         }
      }
      // Could not determine if $identifier was valid.
      else
      {
         return FALSE;
      }

   return TRUE;
   }



   // Perform the encryption (hashing) of the password depending on defined encryption algorithim.  If "plain" is specified
   // as the encryption method, it just returns the input string.  Please don't use plain in production...
   // @param $plaintext string   Plaintext password to encrypt
   // @return string  Encrypted password, FALSE if error.
   public function encryptPassword($plaintext)
   {
      switch (strtoupper($this->encMethod))
      {
         case "SHA1":
         $encpass = sha1($plaintext);
         break;

         case "MD5":
         $encpass = md5($plaintext);
         break;

         case "PLAIN":
         $encpass = $plaintext;
         break;

         default:
         $encpass = FALSE;
         break;
      }

   return $encpass;
   }


   
   // Auth levels are not loaded automatically, so this method queries the database and loads them into the current
   // object.  We get both user level and group levels, and put them in $this->authLevels.
   //
   // To retrieve a list of auth levels, use getAuthLevels() which calls this method, then returns them as an array.
   protected function loadAuthLevels()
   {
      $reader = $this->getReader();
      $id = $reader->e($this->id);

      $query = "SELECT * FROM MrAuthUserLevel
                INNER JOIN MrAuthLevel USING (auth_level_id)
                WHERE MrAuthUserLevel.auth_id = '{$id}'
               ";
               
      $this->userAuthLevels = $reader->queryFetchResults($query, "auth_level_identifier");

      $query = "SELECT * FROM MrAuthGroupUser
                INNER JOIN MrAuthGroupLevel USING (group_id)
                INNER JOIN MrAuthLevel USING (auth_level_id)
                WHERE MrAuthGroupUser.auth_id = '{$id}'
               ";
               
      $this->groupAuthLevels = $reader->queryFetchResults($query, "auth_level_identifier");
      $results = array_merge($this->userAuthLevels, $this->groupAuthLevels);

      foreach ($results as $auth_level_id => $level)
      {
         $this->authLevels[$auth_level_id] = $level['auth_level_name'];
      }
   }


   // MrORM callback function after set() is called. Encrypt password after it is set.
   protected function onAfterSet($key, $value)
   {
      if ($key == "password")
      {      
         if (!$encpass = $this->encryptPassword($value))
         {
            return FALSE;
         }

         $this->password = $encpass;
      }
   }
   
   
   // MrORM callback function.  Make sure we have some default values.
   protected function onBeforeSave()
   {
      if (empty($this->deleted)) $this->deleted = "N";
      if (empty($this->created)) $this->created = date("c");
      if (empty($this->updated)) $this->updated = $this->created;
   }

   
   
   // MrORM callback function.  Determine if we actually loaded a real user.
   protected function onAfterLoad()
   {
      if (MrORM::isSynched() && !empty($this->id))
      {
         $this->validUser = TRUE;
      }
   }

   
// end MrAuthUser class
}


?>
