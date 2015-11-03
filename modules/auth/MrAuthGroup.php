<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Authentication Layer
//
// This class represents an authentication group, which has auth levels
// assigned to it.  Any user added to a group will inherit the auth levels
// of the group automatically.

mrlib::load("sql/MrORM");
mrlib::load("auth/MrAuthUser");

class MrAuthGroup extends MrORM
{
   public $id;
   public $identifier;
   public $name;
   public $MrAuthUser;

   // Array of auth levels in key=>value format, where key is the identifier and value is name of level, eg:
   // AuthLevels['ADMIN'] = "Admin Auth Level"
   public $authLevels = array();


   function __construct()
   {
        MrORM::__construct();
        MrORM::addMap("id",            "group_id");
        MrORM::addMap("identifier",    "group_identifier");
        MrORM::addMap("name",          "group_name");
        $this->addPrimaryKey("id");
        $this->addRelation(ORM_HAS_AND_BELONGS_TO_MANY, "MrAuthUser", "MrAuthGroupUser");
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
   
   
   
   // Assign an auth level to the current group.
   // @param $identifier (string) The auth level identifier string
   // @return TRUE/FALSE
   public function addAuthLevel($identifier)
   {
      $writer = $this->getWriter();
      $level = new MrAuthLevel();
      $level->loadByIdentifier($identifier);

      if (!empty($level->id) && $level->isSynched() == TRUE)
      {
         $insert['group_id'] = $this->id;
         $insert['auth_level_id'] = $level->id;

         if (!$writer->doInsertIgnore("MrAuthGroupLevel", $insert))
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



   // Remove an auth level from this group.
   // @param $identifier (string) The auth level identifier string
   // @return TRUE/FALSE
   public function removeAuthLevel($identifier)
   {
      $writer = $this->getWriter();
      $level = new MrAuthLevel();
      $level->loadByIdentifier($identifier);

      if (!empty($level->id) && $level->isSynched() == TRUE)
      {
         $conditions[] = "group_id = {$this->id}";
         $conditions[] = "auth_level_id = {$level->id}";
         
         if (!$writer->doDelete("MrAuthGroupLevel", $conditions, "LIMIT 1"))
         {
            return FALSE;
         }
         
         // only modify local array if we know for sure that the query was successful, and removed only 1 record
         if ($writer->affectedRows() == 1 && isset($this->authLevels[$identifier]))
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
   
   
    // Add a user to this group by passing in a MrAuthUser object.  This will create a relationship in the
    // database linking the user to this group.
    // @param $user (MrAuthUser)  A MrAuthUser object with necessary information filled out.
    public function addUser(MrAuthUser $user)
    {
        $writer = $this->getWriter();
        $user->save();
        
        if (!empty($user->id) && $user->isSynched() == TRUE)
        {
            $fields['group_id'] = $this->id;
            $fields['auth_id'] = $user->id;
            if (!$writer->doInsertIgnore("MrAuthGroupUser", $fields))
            {
                return FALSE;
            }
        }
        else
        {
            // Not a valid user.
            return FALSE;
        }
        
    return TRUE;
    }
   
   
    // Remove the specified user from the group.  Pass in a valid MrAuthUser object and this will remove
    // the relationship in the database. The user will no longer be in the group and will no longer inherit
    // this group's permissions.
    // @param $user (MrAuthUser)  A MrAuthUser object with necessary information filled out.
    public function removeUser(MrAuthUser $user)
    {
        if (!empty($user->id) && $user->isSynched() == TRUE)
        {
            $conditions = array();
            $conditions[] = "group_id = {$this->id}";
            $conditions[] = "auth_id = {$user->id}";
            if (!$writer->doDelete("MrAuthGroupUser", $conditions, "LIMIT 1"))
            {
                return FALSE;
            }
        }
        else
        {
            // Not a valid user
            return FALSE;
        }
        
    return TRUE;
    }
   
   
   
   // auth levels are not loaded automatically.  Load this to get them from the database.
   public function loadAuthLevels()
   {
      $reader = $this->getReader();
      $id = $reader->e($this->id);

      $query = "SELECT group_id, auth_level_id, auth_level_identifier, auth_level_name
                FROM MrAuthGroupLevel
                INNER JOIN MrAuthLevel USING (auth_level_id)
                WHERE MrAuthGroupLevel.group_id = '{$id}'
               ";
               
      $results = $reader->queryFetchResults($query, "auth_level_identifier");
      
      foreach ($results as $auth_level_id => $level)
      {
         $this->authLevels[$auth_level_id] = $level['auth_level_name'];
      }
   }
   

// end MrAuthGroup class
}


?>
