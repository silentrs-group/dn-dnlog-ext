<?php
namespace develnext\bundle\dnlog\logger;

use gui;

abstract class AbstractFilter 
{
    protected $filter;
    
    /**
     * @var array 
     */
    protected $logUIData = [];
    
    
    abstract public function has ($string, $level, $searchString);
    
    /* public function find ($string, $level, $data)
    {
        if (is_array($string)) {
            foreach ($string as $str) {
                $this->has($str, $level, $data);
            }
        } else {
            return $this->has($string, $level, $data);
        }
    } */
    
    /**
     * @return UXScrollPane
     */
    protected function getMainContainer ()
    {
        return app()->form("MainForm")->container;
    }
    
    public function getFilter ()
    {
        return $this->filter;
    }
}