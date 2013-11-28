<?php namespace Skovachev\Fakefactory;

function ff_method_exists($obj, $methodName)
{
    if ($methodName == 'getNonExistingAttributeFakeValue')
    {
        return false;
    }
    return true;
}

function ff_call_user_func()
{
    return 'fakedValue';
}

use TestCase;
use Mockery;
use Skovachev\Fakefactory\Faker;
use DummyFakerClass;
use Skovachev\Fakefactory\Facade as FakeFactory;

class FakerTest extends TestCase {

    protected $f;
    protected $rulesManager;

    protected function getFaker()
    {
        $class = 'fooClass';
        $this->rulesManager = Mockery::mock('Skovachev\Fakefactory\Faker\RulesManager');
        $this->f = Mockery::mock('Faker\Generator');

        return new Faker($class, $this->rulesManager, $this->f);
    }

    protected function setFakerBlueprint(&$faker, $blueprint, $rule = null, $attributeName = 'foobar')
    {
        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attributes = array($attribute);

        $attribute->shouldReceive('getName')->andReturn($attributeName);
        $this->rulesManager->shouldReceive('createRuleForAttribute')->once()->with($attribute)->andReturn($rule);

        $blueprint->shouldReceive('getAttributes')->andReturn($attributes);

        $faker->setClassBlueprint($blueprint);
    }

    public function testSetClassBlueprint()
    {
        $faker = $this->getFaker();
        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        
        $this->setFakerBlueprint($faker, $blueprint, 'fooRule');

        $this->assertEquals($faker->getRules(), array('foobar' => 'fooRule'));
    }

    public function testUserDefinedRuleExists()
    {
        $class = 'fooClass';
        $this->rulesManager = Mockery::mock('Skovachev\Fakefactory\Faker\RulesManager');
        $this->f = Mockery::mock('Faker\Generator');

        $faker = new DummyFakerClass($class, $this->rulesManager, $this->f);
        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');

        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attributes = array($attribute);

        $attribute->shouldReceive('getName')->andReturn('foo');

        $blueprint->shouldReceive('getAttributes')->once()->andReturn($attributes);

        $faker->setClassBlueprint($blueprint);

    }

    public function testFakeAttributesWithoutRuleArguments()
    {
        $faker = $this->getFaker();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $rule = Mockery::mock('Skovachev\Fakefactory\Faker\Rule');
        $rule->shouldReceive('isCustom')->andReturn(false);
        $rule->shouldReceive('getMethodName')->andReturn('ruleMethodName');
        $rule->shouldReceive('hasArguments')->andReturn(false);

        $this->f->shouldReceive('format')->with('ruleMethodName')->once()->andReturn('fakedValue');

        $this->setFakerBlueprint($faker, $blueprint, $rule);

        $attributes = $faker->fakeAttributes();

        $this->assertEquals($attributes, array('foobar' => 'fakedValue'));
    }

    public function testFakeAttributesWithRuleArguments()
    {
        $faker = $this->getFaker();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $rule = Mockery::mock('Skovachev\Fakefactory\Faker\Rule');
        $rule->shouldReceive('isCustom')->andReturn(false);
        $rule->shouldReceive('getMethodName')->andReturn('ruleMethodName');
        $rule->shouldReceive('hasArguments')->once()->andReturn(true);
        $args = array('bar' => 'baz');
        $rule->shouldReceive('getArguments')->once()->andReturn($args);

        $this->f->shouldReceive('ruleMethodName')->once()->andReturn('fakedValue');

        $this->setFakerBlueprint($faker, $blueprint, $rule);

        $attributes = $faker->fakeAttributes();

        $this->assertEquals($attributes, array('foobar' => 'fakedValue'));
    }

    public function testFakeAttributesWithNoRule()
    {
        $faker = $this->getFaker();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');

        $this->setFakerBlueprint($faker, $blueprint, null);

        $attributes = $faker->fakeAttributes();

        $this->assertEquals($attributes, array('foobar' => null));
    }

    public function testFakeAttributesWithCustomRule()
    {
        $faker = $this->getFaker();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $rule = Mockery::mock('Skovachev\Fakefactory\Faker\Rule');
        $rule->shouldReceive('isCustom')->andReturn(true);

        $this->setFakerBlueprint($faker, $blueprint, $rule);

        $attributes = $faker->fakeAttributes();

        $this->assertEquals($attributes, array('foobar' => 'fakedValue'));
    }

    public function testThrowExceptionIfCustomRuleMethodDoesNotExist()
    {
        $faker = $this->getFaker();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $rule = Mockery::mock('Skovachev\Fakefactory\Faker\Rule');
        $rule->shouldReceive('isCustom')->andReturn(true);

        $this->setFakerBlueprint($faker, $blueprint, $rule, 'nonExistingAttribute');

        $this->setExpectedException('Skovachev\Fakefactory\Exceptions\InvalidFactoryConfigurationException');

        $attributes = $faker->fakeAttributes();
    }

    public function testFakeRelations()
    {
        $faker = $this->getFaker();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $relation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation');
        $relations = array($relation);

        $relationName = 'relName';
        $relatedClassName = 'foobar';

        $overrides = array();
        $overrides[$relationName] = 'baz';

        FakeFactory::shouldReceive('make')->with($relatedClassName, $overrides[$relationName])->once()->andReturn('classFake');

        $relation->shouldReceive('getRelatedClassName')->andReturn($relatedClassName);
        $relation->shouldReceive('getName')->andReturn($relationName);
        $relation->shouldReceive('isToManyRelation')->andReturn(false);
        // $this->rulesManager->shouldReceive('createRuleForAttribute')->once()->with($attribute)->andReturn($rule);

        $blueprint->shouldReceive('getAttributes')->andReturn(array());
        $blueprint->shouldReceive('getRelations')->andReturn($relations);

        $faker->setClassBlueprint($blueprint);

        $relations = $faker->fakeRelations($overrides);

        $this->assertEquals($relations, array('relName' => 'classFake'));
    }

    public function testFakeRelationsIsToManyWrapsClassFakesInArray()
    {
        $faker = $this->getFaker();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $relation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation');
        $relations = array($relation);

        $relationName = 'relName';
        $relatedClassName = 'foobar';

        $overrides = array();
        $overrides[$relationName] = 'baz';

        FakeFactory::shouldReceive('make')->with($relatedClassName, $overrides[$relationName])->once()->andReturn('classFake');

        $relation->shouldReceive('getRelatedClassName')->andReturn($relatedClassName);
        $relation->shouldReceive('getName')->andReturn($relationName);
        $relation->shouldReceive('isToManyRelation')->andReturn(true);

        $blueprint->shouldReceive('getAttributes')->andReturn(array());
        $blueprint->shouldReceive('getRelations')->andReturn($relations);

        $faker->setClassBlueprint($blueprint);

        $relations = $faker->fakeRelations($overrides);

        $this->assertEquals($relations, array('relName' => array('classFake')));
    }

    public function testMergeFakingRules()
    {
        $faker = $this->getFaker();
        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        
        $this->setFakerBlueprint($faker, $blueprint, 'fooRule');

        $additionalRules = array('foo' => 'randomNumber');
        $this->rulesManager->shouldReceive('parseAttributeRule')->with('foo', 'randomNumber')->andReturn('bar');

        $faker->mergeFakingRules($additionalRules);

        $this->assertEquals($faker->getRules(), array('foobar' => 'fooRule', 'foo' => 'bar'));
    }

}