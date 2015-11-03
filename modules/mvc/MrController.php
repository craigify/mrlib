<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// MrController base class
//

class MrController
{
    protected $inputProxy;
    protected $outputProxy;
    protected $eventHandler;

    public function __construct()
    {
        $this->inputProxy = mrlib::getSingleton("proxy/MrProxyManager")->getInputProxy();
        $this->outputProxy = mrlib::getSingleton("proxy/MrProxyManager")->getOutputProxy();
    }


    // Set a reference to the event handler object if the controller is used with an EventHandler.
    // @param (MrEventHandler) $obj  Reference to the event handler object associated with this controller.
    public function setEventHandler($obj)
    {
        $this->eventHandler = $obj;
    }
    
    
    // Retrieve a reference to the event handler.  Since you'll most likely be extending this class to power your higher level
    // controller, you can also just reference parent::eventHandler.
    // @return (EventHandler)  Returns EventHandler object.
    public function getEventHandler()
    {
        return $this->eventHandler;
    }


    // Include a view page from the views directory and return its output as a string.
    // @param  (string) $viewfile   Name of the filename in the views directory
    // @param  (array)  $data       Array of key, value pairs representing variables that you want to have defined in the view file
    // @return (bool|string) $res   The output of the executed view file, FALSE on error.
    public function getView($viewfile, $data=array())
    {
        $fullfile = MRLIB_VIEWS_DIR . "/" . $viewfile;

        if (!file_exists($fullfile))
        {
            return FALSE;
        }
        
        // If you passed in an array of key, value pairs in $data, we want to make every $key in the array available
        // as a local variable in this scope (and in the view file).
        if (is_array($data))
        {
            foreach ($data as $key => $value)
            {
                $$key = $value;
            }
        }
        
        ob_start();
        
        if (!include_once($fullfile))
        {
            ob_end_clean();
            return FALSE;
        }
        
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
    
    
    // Include a view page from the views directory and append its output to the output proxy buffer
    // @param (string) $viewfile   Name of the filename in the views directory
    // @param (array)  $data       Array of key, value pairs representing variables that you want to have defined in the view file
    // @return (bool)  $res        TRUE if view file was successfully read and appended to output, FALSE on error.
    public function appendView($viewfile, $data=array())
    {
        $output = $this->getView($viewfile, $data);

        if ($output === FALSE)
        {
            return FALSE;
        }
        else
        {
            $this->outputProxy->appendOutput($output);            
        }
        
        return TRUE;        
    }

    
    public function appendViewOrig($viewfile, $data=array())
    {
        $fullfile = MRLIB_VIEWS_DIR . "/" . $viewfile;

        if (!file_exists($fullfile))
        {
            return FALSE;
        }
        
        // If you passed in an array of key, value pairs in $data, we want to make every $key in the array available
        // as a local variable in this scope (and in the view file).
        if (is_array($data))
        {
            foreach ($data as $key => $value)
            {
                $$key = $value;
            }
        }
        
        ob_start();
        
        if (!include_once($fullfile))
        {
            ob_end_clean();
            return FALSE;
        }
        
        $output = ob_get_contents();
        ob_end_clean();

        $this->outputProxy->appendOutput($output);
        return TRUE;        
    }
    
    

    // Stop the execution of the event handler, and dump the contents of the output proxy.  Then exit.
    public function stopExecution()
    {
       $this->outputProxy->displayOutput();
       exit();
    }


    // Get a reference to myself.
    public function getController()
    {
       return $this;
    }

    
    
    
// end class
}


?>
