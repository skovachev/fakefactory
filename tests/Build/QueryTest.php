<?php

use Skovachev\Fakefactory\Build\Query;

class QueryTest extends TestCase {
    
    public function __construct()
    {
        $this->factory = Mockery::mock('Skovachev\Fakefactory\Factory');
        $this->config = Mockery::mock('Illuminate\Config\Repository');
    }

    protected function runOptionTest($optionKey, $expectedValue, $queryCallback)
    {
        $this->config->shouldReceive('get')->once()->with('fakefactory::generate_id')->andReturn(false);

        $query = new Query($this->factory, $this->config);
        $buildOptions = array(
            'generate_id' => false,
            'override_attributes' => array(),
            'with' => array(),
            'exclude_attributes' => array(),
            'override_rules' => array()
        );

        $this->assertEquals($buildOptions, $query->getBuildOptions(), 'Default build options have changed');

        $buildOptions[$optionKey] = $expectedValue;

        $queryCallback($query);

        $this->assertEquals($buildOptions, $query->getBuildOptions());
    }

    public function testGenerateIdOption()
    {
        $this->runOptionTest('generate_id', true, function($query){
            $query->generateId();
        });
    }

    public function testLoadRelationsOption()
    {
        $this->runOptionTest('with', array('foo', 'bar'), function($query){
            $query->with('foo', 'bar');
        });
    }

    public function testOverrideAttributesOption()
    {
        $this->runOptionTest('override_attributes', array('foo' => 'bar'), function($query){
            $query->overrideAttributes(array('foo' => 'bar'));
        });
    }

    public function testCustomRulesOption()
    {
        $this->runOptionTest('override_rules', array('foo' => 'bar'), function($query){
            $query->overrideRules(array('foo' => 'bar'));
        });
    }

    public function testOverrideAttributesParameter()
    {
        $this->config->shouldReceive('get')->once()->with('fakefactory::generate_id')->andReturn(false);

        $query = new Query($this->factory, $this->config);
        $makeClass = 'foo';

        $buildOptions = array(
            'generate_id' => false,
            'override_attributes' => array('foo' => 'bar', 'bar' => 'baz'),
            'with' => array(),
            'exclude_attributes' => array(),
            'override_rules' => array()
        );

        $this->factory->shouldReceive('setBuildOptions')->once()->with($buildOptions);
        $this->factory->shouldReceive('make')->once();

        $query->overrideAttributes(array('foo' => 'bar'))->make($makeClass, array('bar' => 'baz'));
    }

    public function testExcludeAttributesOption()
    {
        $this->runOptionTest('exclude_attributes', array('foo', 'bar'), function($query){
            $query->excludeAttributes('foo', 'bar');
        });
    }

    public function testTriggerFactoryMake()
    {
        $this->config->shouldReceive('get')->once()->with('fakefactory::generate_id')->andReturn(false);

        $query = new Query($this->factory, $this->config);
        $makeClass = 'foo';

        $this->factory->shouldReceive('setBuildOptions')->once();
        $this->factory->shouldReceive('make')->once()->with($makeClass);

        $query->make($makeClass);
    }

    public function testTriggerFactoryCreate()
    {
        $this->config->shouldReceive('get')->once()->with('fakefactory::generate_id')->andReturn(false);

        $query = new Query($this->factory, $this->config);
        $makeClass = 'foo';

        $this->factory->shouldReceive('setBuildOptions')->once();
        $this->factory->shouldReceive('create')->once()->with($makeClass);

        $query->create($makeClass);
    }
}