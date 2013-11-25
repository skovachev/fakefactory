<?php namespace Skovachev\Fakefactory\Model\Blueprint;

class Blueprint
{
    protected $attributes = array();
    protected $relations = array();
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function mergeAttributes($attributes)
    {
        $this->mergeItems('attributes', $attributes);
    }

    public function mergeRelations($relations)
    {
        $this->mergeItems('relations', $relations);
    }

    protected function mergeItems($key, $items)
    {
        $this->$key = array_merge($this->$key, $items);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getRelations()
    {
        return $this->relations;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function initializeAttributes($attributes)
    {
        $this->initializeItems('attributes', $attributes);
    }

    protected function initializeItems($key, $items)
    {
        foreach ($this->$key as &$item) {
            if (array_key_exists($item->getName(), $items))
            {
                $item->setValue($items[$item->getName()]);
            }
        }
    }

    public function initializeRelations($relations)
    {
        $this->initializeItems('relations', $relations);
    }
}