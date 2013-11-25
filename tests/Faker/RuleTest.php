<?php

use Skovachev\Fakefactory\Faker\Rule;

class RuleTest extends TestCase {
    
    public function testIsCustom()
    {
        $rule = new Rule('custom');
        $this->assertTrue($rule->isCustom());

        $rule = new Rule('methodName');
        $this->assertFalse($rule->isCustom());
    }

    public function testHasArguments()
    {
        $args = array('foo', 'bar');

        $rule = new Rule('foo');
        $this->assertFalse($rule->hasArguments());

        $rule = new Rule('foo', $args);
        $this->assertTrue($rule->hasArguments());
    }
}