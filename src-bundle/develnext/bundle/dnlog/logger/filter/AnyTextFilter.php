<?php
namespace develnext\bundle\dnlog\logger\filter;

use develnext\bundle\dnlog\logger\AbstractFilter;
use develnext\bundle\dnlog\logger\UI\LogDataLine;
use php\lib\str;
use std;

class AnyTextFilter extends AbstractFilter
{

    public function has ($string, $level, $searchString)
    {
        if (str::contains($searchString, ':')) {
            return true;
        }
        
        return str::contains($string->data("raw")[LogDataLine::D_FULL_LINE], trim($searchString));
    }
}