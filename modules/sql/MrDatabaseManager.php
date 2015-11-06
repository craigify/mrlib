<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrLib Database Library - Database Connection Factory & Manager
//
// Convenient way to store and retrieve references to database objects.
//
// How to use:
// ------------------------------------------------------------------------------------
//
// MrDatabaseManager has built in methods for reader and writer db objects:
//   $dbmanager = mrlib::getSingleton("MrDatabaseManager");
//   $reader = new MrMySQL("hostname", "user", "pass", "db_name");
//   $writer = new MrMySQL("hostname", "user", "pass", "db_name");
//   $dbmanager->setReader($reader);
//   $dbmanager->setWriter($writer);
//
// Factory method for creating general database connection objects:
//   $myConnection = new MrMySQL("hostname", "user", "pass", "db_name");
//   $dbmanager->setDb("MyConnection", $myConnection);
//
// Then you can get a reference anywhere...
//   $reader = mrlib::getSingleton("MrDatabaseManager")->getReader();
//   $reader->doSelect(blah blah);
//
//   $myConn = mrlib::getSingleton("MrDatabaseManager")->getDb("MyConnection");
//   $myConn->doSelect(blah blah);
//

mrlib::load("core/MrSingleton");

class MrDatabaseManager extends MrSingleton
{
    private $dbConnections = array();


    // Set the credentials for the read connection.  This sets up a db object with the identifier of "reader"
    // @param (MrSQL) $object   A MrSQL derived object that you created.
    public function setReader($object)
    {
        $this->setDb("reader", $object);
    }    

    
    // Set the credentials for the write connection.  We use this information to connect to the database server for writing.
    //
    // NOTE - Eventually we will set the reader and writer by passing in an object of a DB object you created instead of
    // tightly coupling the db object to the manager.  For now this remains...
    public function setWriter($object)
    {
        $this->setDb("writer", $object);
    }
    
    
    // Return a copy of the reader database object, representing a read connection to the database server.
    // @return (MrSQL) MrSQL reader object.
    public function getReader()
    {
        return $this->getDb("reader");
    }
    
    
    // Return a copy of the writer database object, representing a write connection to the database server.
    // @return (MrSQL) MrSQL writer object.
    public function getWriter()
    {
        return $this->getDb("writer");
    }
    

    // Set/store a db object with the manager class.  Use GetDb("identifier") to retrieve that object later.
    // @oaram (string)  $identifier   A string used to identify your connection.
    // @param (MrSQL)   $object       A MrSQL derived object that you created.
    public function setDb($identifier, $object)
    {
        $this->dbConnections[$identifier] = $object;
    }


    // Retrieve a db object.  Returns object or FALSE on error.
    //
    // @param (string)  $identifier  The string used to identify the connection to retrieve, that was set by setDb()
    // @return (MrSQL)  A MrSQL derived object that you created.
    public function getDb($identifier)
    {
        if (isset($this->dbConnections[$identifier]))
        {
            return $this->dbConnections[$identifier];
        }
        else
        {
            return FALSE;
        }
    }

    
    // Close a database connection and forget it, removing any reference of the object in the database manager.
    // @param (string)  $identifier  The string used to identify the connection to retrieve, that was set by setDb()
    public function closeDb($identifier)
    {
        if (isset($this->dbConnections[$identifier]))
        {
            $this->dbConnections[$identifier]->close();
            unset($this->dbConnections[$identifier]);
        }
    }

    
    // Remove any reference of the object in the database manager, but keep the connection open to the db server.
    // @param (string)  $identifier  The string used to identify the connection to retrieve, that was set by setDb()
    public function forgetDb($identifier)
    {
        if (isset($this->dbConnections[$identifier]))
        {
            unset($this->dbConnections[$identifier]);
        }
        
    }


    // Constructor
    function __construct()
    {
        $this->readerInfo = array();
        $this->writerInfo = array();
        $this->isConnected = FALSE;
    }



    // Override MrSingleton method and pass in our class name string
    public static function getInstance($classname = __CLASS__)
    {
        return parent::getInstance($classname);
    }
    
    
    
// end MrDatabaseManager class
}



?>
