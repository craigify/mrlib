<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Authentication Factory and Manager Singleton. Provide a convenient way to store and retrieve
// references to authentication handlers.
//
// How to use:
// ------------------------------------------------------------------------------------
//   $manager = mrlib::getSingleton("MrAuthManager");
//   $myHandler = new MyAuthHandler();
//   $manager->setAuthHandler("myhandler", $myHandler);
//
// Somewhere else in your code:
// ------------------------------------------------------------------------------------
//   $manager = mrlib::getSingleton("MrAuthManager");
//   $myHandler = $manager->getAuthHandler("myhandler");
//
// You can also chain:
//   $myHandler = mrlib::getSingleton("MrAuthManager")->getAuthHandler("myhandler");
//

mrlib::load("core/MrSingleton");
mrlib::load("auth/MrAuthHandler");

class MrAuthManager extends MrSingleton
{
    private $authHandler;


    // Get the current authentication handler by identifier and return a reference to it. If there
    // is no auth handler object found with the specified identifier, return FALSE.
    //
    // @param string $identifier The identifier to represent your auth handler.
    // @return boolean|MrAuthHandlerInterface Object that implements MrAuthHandlerInterface, or FALSE
    public function getAuthHandler($identifier)
    {
        if (isset($this->authHandler[$identifier]))
        {
            return $this->authHandler[$identifier];
        }
        else
        {
            return FALSE;
        }
    }


    // Store a reference to your Auth Handler object.
    // @param string $identifier The identifier to represent your auth handler.
    // @param MrAuthHandlerInterface $objectRef Object that implements MrAuthHandlerInterface.
    public function setAuthHandler($identifier, $objectRef)
    {
        $this->authHandler[$identifier] = $objectRef;
    }
    

    // Constructor
    function __construct()
    {
        $this->authHandler = array();
    }


    // Override MrSingleton method and pass in our class name string
    public static function getInstance($classname = __CLASS__)
    {
        return parent::getInstance($classname);
    }
    
}



?>
