<?php

namespace develnext\bundle\dnlog\logger;

use develnext\bundle\dnlog\logger\filter\AnyTextFilter;
use develnext\bundle\dnlog\logger\UI\LogDataLine;
use framework;
use ide\Ide;
use ide\Logger;
use std;
use gui;

class LineFilter
{
    private static $filters = [];
    private static $anyText;
    /**
     * @var int
     */
    public static $classDataSize;

    public static function register(AbstractFilter $filter)
    {
        self::$filters[get_class($filter)] = $filter;

        if (empty(self::$anyText)) {
            self::$anyText = new AnyTextFilter();
        }
    }

    public static function unregister($filter)
    {
        unset(self::$filters[get_class($filter)]);
    }

    public static function filter($string, $level, $data)
    {
        $filterList = [];

        if (is_array($string)) {
            $filterList = $string;
        } else {
            $filterList[] = $string;
        }


        if (Ide::get()->getMainForm()->layout->data("list-container")) {
            Ide::get()->getMainForm()->layout->data("list-container")->content->children->clear();
            self::$classDataSize = 0;

            $data = flow($data)->find(function (LogDataLine $item) use ($level) {
/*
                // if (self::$classDataSize < ($l = UXFont::getDefault()->calculateTextWidth($item->data("raw")[LogDataLine::D_CLASS]))) {
                if (self::$classDataSize < ($l = $item->getNode()
                        ->children[LogDataLine::D_CLASS]->font
                        ->calculateTextWidth($item->data("raw")[LogDataLine::D_CLASS]))) {
                    self::$classDataSize = $l;
                }*/

                return $level == 'All' || $item->data("raw")[LogDataLine::D_LOG_LEVEL] == $level;
            })->toArray();

            foreach (self::$filters as $filter) {

                if (count($data) == 0) break;

                $data = flow($data)->find(function (LogDataLine $item) use ($filter, $filterList, $level) {
                    foreach ($filterList as $searchString) {
                        if ($searchString == "") continue;

                        if (!$filter->has($item, $level, $searchString)) {
                            return false;
                        }
                    }

                    return self::$anyText->has($item, $level, $searchString);
                })->toArray();
            }

            flow($data)->each(function (LogDataLine $item) {
                // $item->getNode()->children[LogDataLine::D_CLASS-1]->css("-fx-min-width", self::$classDataSize);
                Ide::get()->getMainForm()->layout->data("list-container")->content->children->add($item->getNode());
            });
        }
    }

    public static function getAllStringFilters()
    {
        $filters = [];

        foreach (self::$filters as $filter) {
            if ($filter->getFilter() == null) continue;
            $filters[] = $filter->getFilter();
        }

        return $filters;
    }

}