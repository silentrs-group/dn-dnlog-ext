<?php
namespace develnext\bundle\dnlog\logger\UI;

use develnext\bundle\dnlog\filteredComponent\Eventable;
use gui;
use php\gui\layout\UXHBox;
use php\gui\UXTitledPane;
use php\lib\str;

class LogDataLine 
{
    const D_FULL_LINE = 0;
    const D_LOG_LEVEL = 1;
    const D_TIME      = 2;
    const D_CLASS     = 3;
    const D_MESSAGE   = 4;
    
    /**
     * @var UXHBox
     */
    private $container;
    private $level;
    private $class;
    private $time;
    private $message;
    
    use Eventable;
    
    public function __construct ()
    {
        $this->container = new UXHBox([
            $this->level   = $this->makeLabel("level"),
            $this->class   = $this->makeLabel("class"),
            $this->time    = $this->makeLabel("time"),
            $this->message = $this->makeLabel("message")
        ]);

        $this->level->alignment = "CENTER_RIGHT";
        $this->level->css("-fx-min-width", "50");
        $this->class->css("-fx-min-width", "120");

        $this->message->autoSize = true;
        UXHBox::setHgrow($this->message, 'ALWAYS');
        UXHBox::setHgrow($this->container, 'ALWAYS');
        
        $this->container->spacing     = 5;
        $this->container->padding     = 3;
        $this->container->paddingLeft = 5;
        $this->class->cursor          = 'HAND';
    }
    
    public function setLevel ($val)
    {
        $this->level->text = $val;
    }
    
    public function setClass ($val)
    {
        $this->class->text = $val;
    }
    
    public function setTime ($val)
    {
        $this->time->text = $val;
    }
    
    public function setMessage ($val)
    {
        $this->message->text = $val;

        if (($lines = str::lines($val) > 1) || str::length($val) >= 120) {
            if ($lines > 1) {
                $this->_message = new UXTitledPane(str::sub($lines[0], 0, 120));
            } else {
                $this->_message = new UXTitledPane(str::sub($val, 0, 120));
            }

            $this->_message->expanded = false;
            $this->container->children->replace($this->message, $this->_message);

            UXHBox::setHgrow($this->_message, 'ALWAYS');

            $this->_message->content = new UXHBox([$this->message]);


        }
    }
    
    public function getNode ()
    {
        return $this->container;
    }
    
    private function makeLabel ($name)
    {
        $label = new UXLabelEx("null");
        $label->on("click", function ($e) use ($name) {
            $this->trigger($name . ".click", [$e]);
        });
        $label->classes->add("log-data-" . $name);
        
        return $label;
    }
    
    public function data ($name, $value = null)
    {
        if ($value === null) {
            return $this->container->data($name);
        }
        
        $this->container->data($name, $value);
    }
}