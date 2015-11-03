<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Proxy Manager.  Manage global input and output proxies.  To set your own input and
// output proxies, e.g.:
//
// mrlib::getSingleton("proxy/MrProxyManager")->setInputProxy(new ExtendedInputProxy());
// mrlib::getSingleton("proxy/MrProxyManager")->setOutputProxy(new ExtendedOutputProxy());
//

class MrProxyManager extends MrSingleton
{
    private $inputProxy;
    private $outputProxy;


    public function setInputProxy($proxy)
    {
        $this->inputProxy = $proxy;
    }


    public function getInputProxy()
    {
        if (!$this->inputProxy)
        {
            $this->inputProxy = mrlib::getNew("proxy/MrInputProxy");
        }
        
        return $this->inputProxy;
    }
    

    public function setOutputProxy($proxy)
    {
        $this->outputProxy = $proxy;
    }


    public function getOutputProxy()
    {
        if (!$this->outputProxy)
        {
            $this->outputProxy = mrlib::getNew("proxy/MrOutputProxy");
        }
        
        return $this->outputProxy;
    }


    // Constructor
    function __construct()
    {
        $this->inputProxy = null;
        $this->outputProxy = null;
    }


    // Override MrSingleton method and pass in our class name string
    public static function getInstance($classname = __CLASS__)
    {
        return parent::getInstance($classname);
    }
    
}



?>
