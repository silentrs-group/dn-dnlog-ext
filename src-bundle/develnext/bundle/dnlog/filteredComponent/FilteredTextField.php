<?php
namespace develnext\bundle\dnlog\filteredComponent;

use develnext\bundle\dnlog\filteredComponent\items\SelectedFilter;
use framework;
use gui;
use php\util\Flow;
use std;

class FilteredTextField extends UXHBox
{
    /**
     * @var UXButton
     */
    private $showFiltersButton;
    
    /**
     * @var UXTextField
     */
    private $textFieldFilters;
    
    /**
     * @var UXHBox
     */
    private $filtersContainer;
    
    /**
     * @var UXPopupWindow
     */
    private $popup;
    
    /**
     * @var UXListView
     */
    private $list;
    
    public $filters              = [];
    public $selectedFilters      = [];
    public $history              = [];
    
    private $selectedFiltersNode = [];
    
    private $onListItemEnterSend = false;
    
    use Shortcutable;
    
    public function __construct ()
    {
        parent::__construct();
        
        $this->classes->add('filtered-text-field');
        $this->alignment = 'CENTER';
        $this->spacing = 5;

        $this->add($this->showFiltersButton = new UXButton());
        $this->add($this->filtersContainer  = new UXHBox());
        
        $this->filtersContainer->add($this->textFieldFilters = new UXTextField());
        $this->filtersContainer->alignment = 'CENTER';
        
        $this->textFieldFilters->promptText = 'Нажмите Ctrl+Space чтобы увидеть возможные фильтры';
        
        $this->showFiltersButton->on("click", function () {            
            $this->makePoup();
            
            $this->list->items->addAll($this->history);
            
            $this->popup->showByNode($this, 0, $this->height);
        });
        
        
        // hotkey
        $this->bindShortcut($this, function () {
            $this->makePoup();
            
            $this->list->items->addAll($this->filters);
            
            $this->popup->showByNode($this, 0, $this->height);
        }, "Ctrl+Space");
        
        // select filter from list
        $this->textFieldFilters->on("keyUp", function ($e) {
            if ($this->textFieldFilters->length > 0 && $e->codeName == 'Enter') {
                if ($this->onListItemEnterSend) {
                    $this->onListItemEnterSend = false;
                    return;
                }
                $this->addFilter();
            }
        });
        
        $this->textFieldFilters->on("keyDown", function ($e) {
            if ($this->textFieldFilters->length == 0 && $e->codeName == 'Backspace') {
                $this->doRemoveItem($e);
            } else if ($this->textFieldFilters->caretPosition == 0 && $e->codeName == 'Backspace') {
                $this->doRemoveItem($e);
            }
        });
        
        
        $this->filtersContainer->classes->add('fake-text-field');
        
        UXHBox::setHgrow($this->filtersContainer, 'ALWAYS');
        UXHBox::setHgrow($this->textFieldFilters, 'ALWAYS');
    }
    
    private function addFilter ()
    {
        if ($this->textFieldFilters->text == "") return;
        
        $this->selectedFilters[] = $this->textFieldFilters->text;
        $this->selectedFilters = array_unique($this->selectedFilters);
        
        if (!in_array($this->textFieldFilters->text, $this->history)) {
            $this->history[] = $this->textFieldFilters->text;
        }
        
        $this->selectedFiltersNode[] = $filter = new SelectedFilter($this->textFieldFilters->text, $this);
        $this->filtersContainer->children->insert($this->filtersContainer->children->count - 1, $filter);
        $this->textFieldFilters->text = "";
    }
    
    
    public function getFilterContainer ()
    {
        return $this->filtersContainer;
    }
    
    public function removeFilter ($filter)
    {
        if (($index = array_search($this->selectedFilters, $filter, true)) !== false) {
            unset($this->selectedFilters[$index]);
            $this->selectedFilters = Flow::of($this->selectedFilters)->toArray();
        }

    }
    
    public function setObserverTextField (callable $callback)
    {
        $this->textFieldFilters->watch("text", $callback);
    }
    
    /**
     * @var UXTextField
     */
    public function getTextField ()
    {
        return $this->textFieldFilters;
    }
    
    private function makePoup ()
    {
        if ($this->popup == null) {
            $this->popup = new UXPopupWindow();
            $this->popup->autoFix = true;
            $this->popup->autoHide = true;
            $this->popup->add($this->list = new UXListView());
            $this->list->classes->add('filter-list');
            $this->list->leftAnchor = $this->list->topAnchor = $this->list->rightAnchor = $this->list->bottomAnchor = 0;
            
            $selectItemEvent = function ($e) {
                $this->textFieldFilters->text = $this->list->selectedItem;
                $this->textFieldFilters->positionCaret(strlen($this->list->selectedItem));
                $this->popup->hide();
            };
            
            $this->list->on("click", $selectItemEvent);
            
            $this->list->on("keyDown", function ($e) use ($selectItemEvent) {
                if ($e->codeName == "Enter") {
                    $this->onListItemEnterSend = true;
                    $selectItemEvent($e);
                }
            });
        }
        
        $this->list->items->clear();
        
        $this->list->width = $this->width;
        $this->list->height = 200;
    }
    
    
    
    
    private function doRemoveItem ($e)
    {

        if (count($this->selectedFilters) > 0) {
            $filter = $this->selectedFilters[count($this->selectedFilters)-1]; // arr::last($this->selectedFilters);
            
            foreach ($this->selectedFiltersNode as $selectedFilter) {

                if ($filter === $selectedFilter->getFilter()) {

                    $selectedFilter->removeFilter();
                    /* $this->removeFilter($filter); */
                }
            }
                    
        }
    }
}


/*
события
addFilter 
removeFilter
*/

















