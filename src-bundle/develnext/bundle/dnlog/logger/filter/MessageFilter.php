<?php
namespace develnext\bundle\dnlog\logger\filter;

use develnext\bundle\dnlog\logger\AbstractFilter;
use develnext\bundle\dnlog\logger\UI\LogDataLine;
use ide\Logger;
use php\lib\str;
use std;

class MessageFilter extends AbstractFilter
{
    protected $filter = 'message:';
    
    public function has ($item, $level, $searchString)
    {
        if (str::startsWith($searchString, "message:")) {
            $search = str::split($searchString, ':')[1];

            return str::contains(base64_decode($item->data("raw")[LogDataLine::D_MESSAGE]), $search);
        }
        
        return true;
    }
}