<?php

namespace develnext\bundle\dnlog\filteredComponent;

trait Eventable
{
    private $events = [];

    protected $enable = true;

    public function on($event, $callback, $group = 'main')
    {
        $this->events[$event][$group] = $callback;
    }

    public function off($event, $group)
    {
        $this->events[$event][$group] = null;
    }

    public function trigger($event, $args = [], $group = 'main')
    {
        if (!(isset($this->events[$event][$group])) || !$this->enable) {
            return;
        }

        call_user_func_array($this->events[$event][$group], $args);
    }


    public function isEnabled()
    {
        return $this->enable;
    }

    public function disable()
    {
        $this->enable = false;
    }

    public function enable()
    {
        $this->enable = true;
    }
}