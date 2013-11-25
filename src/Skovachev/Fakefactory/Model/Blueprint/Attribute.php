<?php namespace Skovachev\Fakefactory\Model\Blueprint;

class Attribute extends Item
{
    protected $type;

    public function __construct($name, $type, $value = null)
    {
        parent::__construct($name, $value);
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }
}