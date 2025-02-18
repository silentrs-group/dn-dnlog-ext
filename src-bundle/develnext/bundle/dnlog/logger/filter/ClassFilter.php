<?php
namespace develnext\bundle\dnlog\logger\filter;

use develnext\bundle\dnlog\logger\AbstractFilter;
use develnext\bundle\dnlog\logger\UI\LogDataLine;
use php\lib\str;
use std;

class ClassFilter extends AbstractFilter
{
    protected $filter = 'class:';
    
    public function has ($item, $level, $searchString)
    {
        if (str::startsWith($searchString, "class:")) {
            $search = str::split($searchString, ':')[1];
            
            return str::contains($item->data("raw")[LogDataLine::D_CLASS], $search);
        }
        
        return true;
    }
}