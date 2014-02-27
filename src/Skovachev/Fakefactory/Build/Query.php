<?php namespace Skovachev\Fakefactory\Build;

use App;
use Config;

class Query 
{
    protected $options;
    protected $factory;

    public function __construct($factory = null, $config = null)
    {
        $this->factory = $factory ?: App::make('fakefactory');
        $config = $config ?: Config::getFacadeRoot();

        $this->options = array(
            'generate_id' => $config->get('fakefactory::generate_id'),
            'override_attributes' => array(),
            'with' => array(),
            'exclude_attributes' => array(),
            'rules' => array()
        );
    }

    public function getBuildOptions()
    {
        return $this->options;
    }

    protected function setOption($key, $value)
    {
        if (is_array($this->options[$key]))
        {
            $this->options[$key] = array_merge($this->options[$key], $value);
        }
        else
        {
            $this->options[$key] = $value;
        }
        return $this;
    }

    public function generateId($enabled = null)
    {
        return $this->setOption('generate_id', is_null($enabled) ? true : $enabled);
    }

    public function rules($rules = array())
    {
        return $this->setOption('rules', $rules);
    }

    public function overrideAttributes(array $overrides = array())
    {
        return $this->setOption('override_attributes', $overrides);
    }

    public function excludeAttributes()
    {
        return $this->setOption('exclude_attributes', func_get_args());
    }

    public function with()
    {
        return $this->setOption('with', func_get_args());
    }

    protected function getFactory()
    {
        $this->factory->setBuildOptions($this->options);
        return $this->factory;
    }

    public function make($class, $overrides = array())
    {
        $this->setOption('override_attributes', $overrides);
        return $this->getFactory()->make($class);
    }

    public function create($class, $overrides = array())
    {
        $this->setOption('override_attributes', $overrides);
        return $this->getFactory()->create($class);
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->factory, $method), $args);
    }
}