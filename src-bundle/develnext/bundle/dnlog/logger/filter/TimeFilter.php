<?php
namespace develnext\bundle\dnlog\logger\filter;

use develnext\bundle\dnlog\logger\AbstractFilter;
use develnext\bundle\dnlog\logger\UI\LogDataLine;
use ide\Logger;
use php\lib\str;
use php\time\Time;
use php\time\TimeFormat;
use std;

class TimeFilter extends AbstractFilter
{
    protected $filter = 'time:';
    
    public function has ($string, $level, $searchString)
    {
        if (str::startsWith($searchString, $this->filter)) {
            $userTime = str::split($searchString, ':')[1];
            
            $nowTime   = (new TimeFormat("HH:mm:ss"))->parse(Time::now()->toString("HH:mm:ss"));                               // range A
            $minusTime = (new TimeFormat("HH:mm:ss"))->parse(Time::now()->add(["sec" => -$userTime])->toString("HH:mm:ss"));   // range B
            
            $lineTime = (new TimeFormat("HH:mm:ss"))->parse($string->data("raw")[LogDataLine::D_TIME]);
            
            return $nowTime->compare($lineTime) !== $minusTime->compare($lineTime);
        }
        
        return true;
    }
}