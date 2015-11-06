<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Authentication Manager Singleton
//

mrlib::load("core/MrSingleton");
mrlib::load("auth/MrAuthHandler");

class MrAuthManager extends MrSingleton
{
    private $authHandler;


    // Get the current authentication handler and return it.
    // @return (MrAuthHandler)  A MrAuthHandler object or object that extends MrAuthHandler.
    public function getAuthHandler()
    {
        if ($this->authHandler)
        {
            return $this->authHandler;
        }
        
        // If no auth handler is initialized, use the default one and return it.
        else
        {
            $this->authHandler = new MrAuthHandler();
            return $this->authHandler;
        }
    }


    // Set the authentication handler to a custom object that has extended MrAuthHandler.
    public function setAuthHandler($object)
    {
        $this->authHandler = $object;
    }
    

    // Constructor
    function __construct()
    {
        $this->authHandler = null;
    }


    // Override MrSingleton method and pass in our class name string
    public static function getInstance($classname = __CLASS__)
    {
        return parent::getInstance($classname);
    }
    
}



?>
