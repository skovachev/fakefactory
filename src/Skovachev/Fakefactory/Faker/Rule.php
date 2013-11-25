<?php namespace Skovachev\Fakefactory\Faker;

class Rule
{
    protected $methodName;
    protected $arguments = array();

    public function __construct($methodName, $arguments = array())
    {
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    public function isCustom()
    {
        return $this->methodName == 'custom';
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getMethodName()
    {
        return $this->methodName;
    }

    public function hasArguments()
    {
        return !empty($this->arguments);
    }
}