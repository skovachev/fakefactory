<?php

use Skovachev\Fakefactory\Faker\Rule;
use Skovachev\Fakefactory\Faker\RulesManager;

class RulesManagerTest extends TestCase {

    public function __construct()
    {
        $this->config = Mockery::mock('Illuminate\Config\Repository');
    }

    protected function createManager()
    {
        $this->config->shouldReceive('get')->once()->with('fakefactory::database_type_rules')->andReturn(array('integer' => 'foo'));
        $this->config->shouldReceive('get')->once()->with('fakefactory::special_field_rules')->andReturn(array('email' => 'bar'));
        $manager = new RulesManager($this->config);
        return $manager;
    }
    
    public function testParseAttributeRuleWithStringArgument()
    {
        $manager = $this->createManager();
        $attributeName = 'foo';
        $attributeData = 'bar|baz';

        $rule = $manager->parseAttributeRule($attributeName, $attributeData);

        $this->assertEquals($rule->getMethodName(), 'bar');
        $this->assertEquals($rule->getArguments(), array('baz'));
    }

    public function testParseAttributeRuleWithArrayArgument()
    {
        $manager = $this->createManager();
        $attributeName = 'foo';
        $attributeData = array('bar', 'baz');

        $rule = $manager->parseAttributeRule($attributeName, $attributeData);

        $this->assertEquals($rule->getMethodName(), 'bar');
        $this->assertEquals($rule->getArguments(), array('baz'));
    }

    public function testParseAttributeRuleWithRuleArgument()
    {
        $manager = $this->createManager();

        $attributeName = 'foo';
        $attributeData = new Rule('bar');

        $rule = $manager->parseAttributeRule($attributeName, $attributeData);

        $this->assertEquals($rule, $attributeData);
    }

    public function testCreateRuleForAttributeByName()
    {
        $manager = $this->createManager();
        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attribute->shouldReceive('getName')->once()->andReturn('email');
        $attribute->shouldReceive('getType')->once()->andReturn('text');

        $rule = $manager->createRuleForAttribute($attribute);
        $this->assertEquals('bar', $rule->getMethodName());
    }

    public function testCreateRuleForAttributeByType()
    {
        $manager = $this->createManager();
        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attribute->shouldReceive('getName')->once()->andReturn('test');
        $attribute->shouldReceive('getType')->once()->andReturn('integer');

        $rule = $manager->createRuleForAttribute($attribute);
        $this->assertEquals('foo', $rule->getMethodName());
    }

    public function testCreateRuleForAttributeByNameOverType()
    {
        $manager = $this->createManager();
        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attribute->shouldReceive('getName')->once()->andReturn('email');
        $attribute->shouldReceive('getType')->once()->andReturn('integer');

        $rule = $manager->createRuleForAttribute($attribute);
        $this->assertEquals('bar', $rule->getMethodName());
    }
}