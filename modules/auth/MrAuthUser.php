<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Authentication Layer
//
// This class represents a user in the system.  A user can have auth levels
// assigned to it directly.  Users can be assigned to groups with group auth
// levels which the user will inherit due to the association with the group.
// (This is practically identical to unix user and group permissions)
//
//

mrlib::load("sql/MrORM");
mrlib::load("auth/MrAuthLevel");


class MrAuthUser extends MrORM
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



   // Retrieve all auth levels for this user. Return an array of auth level identifiers and names, e.g:
   //    array("LEVEL1" => "Level 1 auth Level"
   //          "LEVEL2" => "Level 2 auth Level"
   //         )
   //
   // @return (array)   auth level array for the user.  Might be empty if no auth levels defined.
   public function getAuthLevels()
   {
      if (empty($this->authLevels))
      {
         $this->loadAuthLevels();
      }

      return $this->authLevels;
   }



   // Check if this user has a specified auth level.
   // @return (boolean) TRUE if so, FALSE otherwise.
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
   // @param $identifier (string) The auth level identifier string
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



   // Compare password on record to specified password.
   // @param (string)     plaintext password
   // @return (bool)      TRUE if password hash matches, FALSE if not
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


   // Perform the encryption (hashing) of the password depending on defined encryption algorithim.  Return encrypted
   // password on success, FALSE on failure.
   // @param (string)   plaintext password
   // @return (string)  encrypted password, FALSE if error.
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
   public function loadAuthLevels()
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


// end MrAuthUser class
}


?>
