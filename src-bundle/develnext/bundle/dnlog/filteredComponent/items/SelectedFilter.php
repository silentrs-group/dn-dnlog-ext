<?php
namespace develnext\bundle\dnlog\filteredComponent\items;

use gui;

class SelectedFilter extends UXHBox
{
    /**
     * @var UXLabelEx
     */
    private $label;
    
    /**
     * @var UXButton
     */
    private $remove;
    /**
     * @var \develnext\bundle\dnlog\filteredComponent\FilteredTextField
     */
    private $parentContainer;
    
    public function __construct (string $label, $parentContainer)
    {
        parent::__construct();
        
        $this->alignment = 'CENTER';
        $this->parentContainer = $parentContainer;
        $this->padding = 3;
        $this->spacing = 3;
        $this->classes->add('filter-node');
        
        $this->label = new UXLabelEx($label);
        $this->label->autoSize = true;
        
        $this->remove = new UXButton();
        $this->remove->css('-fx-shape', '"M16.043,11.667L22.609,5.1c0.963-0.963,0.963-2.539,0-3.502l-0.875-0.875c-0.963-0.964-2.539-0.964-3.502,0L11.666,7.29  L5.099,0.723c-0.962-0.963-2.538-0.963-3.501,0L0.722,1.598c-0.962,0.963-0.962,2.539,0,3.502l6.566,6.566l-6.566,6.567  c-0.962,0.963-0.962,2.539,0,3.501l0.876,0.875c0.963,0.963,2.539,0.963,3.501,0l6.567-6.565l6.566,6.565  c0.963,0.963,2.539,0.963,3.502,0l0.875-0.875c0.963-0.963,0.963-2.539,0-3.501L16.043,11.667z"');
        $this->remove->css('-fx-min-width',  "12");
        $this->remove->css('-fx-max-width',  "12");
        $this->remove->css('-fx-min-height', "12");
        $this->remove->css('-fx-max-height', "12");
        $this->remove->css('-fx-background-color', "lightgray");

        $this->remove->on("click", [$this, "removeFilter"]);
        
        $this->children->add($this->label);
        $this->children->add($this->remove);
    }
    
    public function removeFilter ()
    {
        $this->parentContainer->getFilterContainer()->children->remove($this);
        $this->parentContainer->removeFilter($this->label->text);
    }
    
    public function getFilter ()
    {
        return $this->label->text;
    }
    
    
    
}