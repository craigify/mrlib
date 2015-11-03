<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrSingleton base class
//


class MrSingleton
{
    private static $instance;

    // *** NOTE ***
    //
    // Classes that extend this must provide a function like this.  PHP 5.3 makes this
    // entire process easier, but this works with many older PHP versions. - Craig
    //
    // This method, when placed in the parent class, will pass the name of the parent
    // class as a string to this method so it can keep a record of its single instance
    // in memory, then serve it back up when necessary.
    //
    //  public static function getInstance($classname = __CLASS__)
    //  {
    //      return parent::getInstance($classname);
    //  }
    //


    public static function getInstance($classname = __CLASS__)
    {
        if (!isset(self::$instance))
        {
            self::$instance = array();
        }
        
        if (!isset(self::$instance[$classname]))
        {
            self::$instance[$classname] = new $classname;
        }
        
        return self::$instance[$classname];
    }
    
    
    // Prevent users to clone the instance
    final public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

}


?>