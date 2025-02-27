<?php

namespace develnext\bundle\dnlog;


use develnext\bundle\dnlog\logger\filter\{ClassFilter, MessageFilter, TimeFilter};
use develnext\bundle\dnlog\logger\LineFilter;
use develnext\bundle\dnlog\filteredComponent\FilteredTextField;
use develnext\bundle\dnlog\logger\UI\LogDataLine;
use dnlogger\DNLogServer;
use ide\Ide;
use ide\Logger;
use php\gui\{layout\UXHBox, layout\UXScrollPane, layout\UXVBox, UXButton, UXComboBox, UXLabelEx, UXTab, UXTabPane};
use php\io\ResourceStream;
use php\lib\str;
use php\lang\{Thread, ThreadPool};
use php\util\Regex;

class DNLog
{
    private $worker;

    public $logUIData = [];

    /**
     * @var \develnext\bundle\dnlog\filteredComponent\FilteredTextField
     */
    public $filter;
    /**
     * @var UXCombobox
     */
    private $level;
    /**
     * @var UXVBox
     */
    private $container;
    /**
     * @var \php\gui\UXButton
     */
    private $clearButton;
    private $autoScroll;
    private $heightDifference;
    private $defaultHeightRatio;

    public function __construct()
    {
        $this->worker = ThreadPool::create(1, 1);

        if (!DNLogServer::$state) {
            Logger::info("Logger server is started on port 11394");
            DNLogServer::start(11394, function ($message) {
                //Logger::error("i.m:".$message);
                $this->makeLine(json_decode($message, true));
            });
        }

        $this->startWorker();
    }

    public function startWorker()
    {
        $this->worker->submit(function () {

            while (true) {
                uiLater(function () {
                    if (($container = $this->getConsoleContainer()) == null) return;

                    if ($container->children[0] instanceof UXTabPane) {
                        return;
                    }

                    $this->replaceConsole($container);
                });

                Thread::sleep(100);
            }
        });
    }


    /**
     * @throws \Exception
     */
    public function getConsoleContainer()
    {
        if (Ide::get()->getMainForm()->contentSplit != null &&
            Ide::get()->getMainForm()->contentSplit->items[1] != null) {
            return Ide::get()->getMainForm()->contentSplit->items[1]->children[0]->children[0];
        }

        return null;
    }

    public function replaceConsole($container)
    {
        $consoleOld = $container->children[0];
        $container->children->remove($consoleOld);
        $container->children->insert(0, $tabPane = new UXTabPane());
        $tabPane->tabClosingPolicy = 'UNAVAILABLE';
        $tabPane->side = 'LEFT';

        UXVBox::setVgrow($tabPane, 'ALWAYS');

        $tabPane->tabs->add($oldConsole = new UXTab("Лог программы"));
        $oldConsole->graphic = new UXHBox();
        $oldConsole->graphic->classes->add("tab-terminal");
        $oldConsole->content = $consoleOld;

        $tabPane->tabs->add($newConsole = new UXTab("DNLog"));
        $newConsole->graphic = new UXHBox();
        $newConsole->graphic->classes->add("tab-dn-log");

        if ($this->container == null) {
            $this->getDNLogger();
        } else {
            $this->container->free();
        }

        $newConsole->content = $this->container;


        Ide::get()->getMainForm()->addStylesheet((new ResourceStream('.data/style/filteredTextField.fx.css'))->toExternalForm());
    }

    public function shutdown()
    {
        $this->worker->shutdownNow();
    }

    /**
     * @throws \Exception
     */
    private function getDNLogger()
    {
        LineFilter::register(new ClassFilter());
        LineFilter::register(new MessageFilter());
        LineFilter::register(new TimeFilter());

        $container = $this->initUI();

        $this->filter->filters = LineFilter::getAllStringFilters();

        return $container;
    }

    /**
     * @throws \Exception
     */
    private function initUI()
    {
        $this->container = new UXVBox();
        $this->container->leftAnchor = $this->container->rightAnchor = 0;
        $this->container->add($controlsContainer = new UXHBox());
        $controlsContainer->add(new UXLabelEx("Log level:"));
        $controlsContainer->add($this->level = new UXCombobox());
        $controlsContainer->add(new UXLabelEx("Filter:"));
        $controlsContainer->add($this->filter = new FilteredTextField());
        $controlsContainer->add($this->clearButton = new UXButton());
        $this->container->add($listContainer = new UXScrollPane(new UXVBox()));

        $controlsContainer->alignment = "CENTER";
        $controlsContainer->spacing = 5;
        $controlsContainer->paddingLeft = 10;

        $this->level->value = 'All';
        $this->level->items->addAll(["All", "DEBUG", "INFO", "WARN", "ERROR"]);

        $controlsContainer->maxHeight = 32;

        // $listContainer->content = ;
        $listContainer->vbarPolicy = 'ALWAYS';

        Ide::get()->getMainForm()->layout->data("list-container", $listContainer);
        Ide::get()->getMainForm()->layout->data("log-filter", $this);

        $this->level->watch("value", function ($node, $var, $old, $new) {
            LineFilter::filter(array_unique($this->filter->selectedFilters), $new, $this->logUIData);
        });

        $this->filter->setObserverTextField(function ($node, $var, $old, $new) {
            $level = $this->level->value;

            LineFilter::filter(flow($this->filter->selectedFilters)->append([$new])->toArray(), $level, $this->logUIData);
        });

        $this->clearButton->css("-fx-shape", '"M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM2 10C2 5.58 5.58 2 10 2C11.85 2 13.55 2.63 14.9 3.69L3.69 14.9C2.63 13.55 2 11.85 2 10ZM10 18C8.15 18 6.45 17.37 5.1 16.31L16.31 5.1C17.37 6.45 18 8.15 18 10C18 14.42 14.42 18 10 18Z"');
        $clearButtonSize = 20;
        $this->clearButton->css("-fx-min-width", $clearButtonSize);
        $this->clearButton->css("-fx-min-height", $clearButtonSize);
        $this->clearButton->css("-fx-max-width", $clearButtonSize);
        $this->clearButton->css("-fx-max-height", $clearButtonSize);
        $this->clearButton->classes->add("clear-button-log");
        $this->clearButton->on('click', function ($ev) use ($listContainer) {
            if ($ev->button != 'PRIMARY') return;

            $this->logUIData = [];
            $listContainer->content->children->clear();
        });

        $listContainer->fitToWidth = true;
        $listContainer->content->observer("height")->addListener(function ($o, $new) use ($listContainer) {
            if ($this->autoScroll) {
                $listContainer->scrollY = 1;
            }

            $this->heightDifference = $new - $o;

            if ($o !== 0 && $this->defaultHeightRatio == null) {
                $this->defaultHeightRatio = $this->heightDifference / $this->container->height;
            }
        });

        $listContainer->observer("vvalue")->addListener(function ($o, $new) {
            if ($o == 1) {
                if (($this->heightDifference / $this->container->height) == $this->defaultHeightRatio) {
                    $this->autoScroll = true;
                    return;
                } else {
                    $this->defaultHeightRatio = $this->heightDifference / $this->container->height;
                }
            }

            $this->autoScroll = ($new == 1);
        });

        UXVBox::setVgrow($controlsContainer, 'ALWAYS');
        UXVBox::setVgrow($listContainer, 'ALWAYS');
        UXHBox::setHgrow($this->filter, 'ALWAYS');

        return $this->container;
    }

    /**
     * @throws \Exception
     */
    public function makeLine($data)
    {
        if (is_array($data)) {
            $this->logUIData[] = $line = new LogDataLine();
            array_unshift($data, implode(' ', $data));

            $line->data("raw", $data);
            $line->setLevel($data[LogDataLine::D_LOG_LEVEL]);
            $line->setClass($data[LogDataLine::D_CLASS]);
            $line->setTime($data[LogDataLine::D_TIME]);
            $line->setMessage(base64_decode($data[LogDataLine::D_MESSAGE]));
            $line->on("class.click", function ($e) {
                $this->filter->getTextField()->text = "class:" . $e->sender->text;
                $this->filter->getTextField()->requestFocus();
                $this->filter->getTextField()->positionCaret($this->filter->getTextField()->length);
            });
        } else {
            $line = $this->paresLine($data);
        }

        if (Ide::get()->getMainForm()->layout->data("list-container")) {
            // Logger::error(var_export($data, true));
            Ide::get()->getMainForm()->layout->data("list-container")->content->add($line->getNode());

            if (count($this->filter->selectedFilters) > 0) {
                LineFilter::filter(array_unique($this->filter->selectedFilters), $this->level->value, $this->logUIData);
                return;
            }
        }

        LineFilter::filter([" "], ($this->level) ? $this->level->value : "All", $this->logUIData);
    }

    /**
     * @throws \php\util\RegexException
     */
    public function paresLine($dataLine)
    {

        // $regex = Regex::of('\[(\w+)\]\s*(.*?)\s*\((\d+:\d+:\d+)\)\s*-\s*(.*)')->with($dataLine);
        $regex = Regex::of('\[(\w+)\]\s*(\d+:\d+:\d+)\s*\((.*?)\)\s*-\s*(.*)')->with($dataLine);


        if ($regex->find()) {
            $data = $regex->all()[0];
            // array_shift($data);

            // var_dump($data);
            $this->logUIData[] = $line = new LogDataLine();
            $line->data("raw", $data);
            $line->setLevel($data[LogDataLine::D_LOG_LEVEL]);
            $line->setClass($data[LogDataLine::D_CLASS]);
            $line->setTime($data[LogDataLine::D_TIME]);
            $line->setMessage($data[LogDataLine::D_MESSAGE]);
            $line->on("class.click", function ($e) {
                $this->filter->getTextField()->text = "class:" . $e->sender->text;
            });

            switch ($data[LogDataLine::D_LOG_LEVEL]) {
                case 'INFO':
                    $color = 'skyblue';
                    break;
                case 'WARN':
                    $color = 'orange';
                    break;
                case 'ERROR':
                    $color = 'darkred';
                    break;
                default:
                    $color = 'lightgray';
            }

            $line->getNode()->classes->add('log-data-line-' . str::lower($data[LogDataLine::D_LOG_LEVEL]));

            return $line;
        }

        return null;
    }
}