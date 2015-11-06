<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrTemplate Cache Singleton.  Manage global template cache.
//

mrlib::load("core/MrSingleton.php");


class MrTemplateCache extends MrSingleton
{
    private $cache;
    
    
    function __construct()
    {
        $this->cache = array();
    }



    public function get($key)
    {
        if (isset($this->cache[$key]))
        {
            return $this->cache[$key];
        }
        else
        {
            return FALSE;
        }
    }
    
    
    public function hasKey($key)
    {
        if (isset($this->cache[$key]))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
        
    }
    
    
    public function set($key, $data)
    {
        $this->cache[$key] = $data;
    }


    // Override MrSingleton method and pass in our class name string
    public static function getInstance($classname = __CLASS__)
    {
        return parent::getInstance($classname);
    }

// end class   
}



?>
