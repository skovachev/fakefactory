<?php

function is_associative_array()
{
    return false;
}

use Skovachev\Fakefactory\Factory;

class FactoryTest extends TestCase {

    protected $modelManager;
    protected $reflector;

    protected function getFactory($factory = null)
    {
        $this->modelManager = Mockery::mock('Skovachev\Fakefactory\Model\ModelManager');
        $this->reflector = Mockery::mock('Skovachev\Fakefactory\Model\Reflector');
        if (is_null($factory))
        {
            return new Factory($this->modelManager, $this->reflector);
        }
        else
        {
            $factory->setReflector($this->reflector);
            $factory->setModelManager($this->modelManager);
            return $factory;
        }
    }

    public function testGetClassFaker()
    {
        $factory = $this->getFactory();
        $class = 'DummyFakerClass';

        $this->reflector->shouldReceive('instantiate')->with('FooFaker', $class)->andReturn('bar');

        $faker = $factory->getClassFaker($class);

        $this->assertEquals('bar', $faker);
    }

    public function testMakeBlueprintModelClass()
    {
        $this->getFactory();

        $class = 'DummyFakerClass';
        $attributes = array('attr' => 'fakeAttr');
        $relations = array('foo' => 'fakeRel');

        $attribute = new \Skovachev\Fakefactory\Model\Blueprint\Attribute('attr', 'integer');
        $idAttr = new \Skovachev\Fakefactory\Model\Blueprint\Attribute('id', 'integer');
        $modelAttributes = array($attribute, $idAttr);
        $relation = new \Skovachev\Fakefactory\Model\Blueprint\Relation('foo', 'BelongsTo', 'FooClass', 'foo_id');
        $modelRelations = array($relation);

        $buildOptions = array(
            'generate_id' => false,
            'override_attributes' => array(),
            'with' => array(),
            'exclude_attributes' => array(),
        );

        $relatedTo = array('foo');

        $factory = Mockery::mock('Skovachev\Fakefactory\Factory')->makePartial();
        $factory->setBuildOptions($buildOptions);
        $faker = Mockery::mock('Skovachev\Fakefactory\Faker');
        $faker->shouldReceive('setClassBlueprint')->once();
        $faker->shouldReceive('fakeAttributes')->once()->andReturn($attributes);
        $faker->shouldReceive('fakeRelations')->once()->andReturn($relations);
        $faker->shouldReceive('getRelatedTo')->once()->andReturn($relatedTo);

        $factory->shouldReceive('getClassFaker')->with($class)->once()->andReturn($faker);
        
        // $factory->model = $this->modelManager;
        $factory->setModelManager($this->modelManager);

        $this->modelManager->shouldReceive('isModelClass')->andReturn(true);
        $this->modelManager->shouldReceive('getAttributesForClass')->once()->with($class)->andReturn($modelAttributes);
        $this->modelManager->shouldReceive('getRelationsForClass')->once()->with($class, $relatedTo, array())->andReturn($modelRelations);

        $blueprint = $factory->makeBlueprint($class);

        $this->assertCount(1, $blueprint->getAttributes());
        $firstAttr = $blueprint->getAttributes();
        $firstAttr = $firstAttr[0];
        $this->assertEquals($firstAttr, $attribute);
        $this->assertEquals($firstAttr->getValue(), 'fakeAttr');

        $this->assertCount(1, $blueprint->getRelations());
        $firstRel = $blueprint->getRelations();
        $firstRel = $firstRel[0];
        $this->assertEquals($firstRel, $relation);
        $this->assertEquals($firstRel->getValue(), 'fakeRel');
    }

    public function testGeneratesIdIfOptionSet()
    {
        $this->getFactory();

        $class = 'DummyFakerClass';
        $attributes = array('attr' => 'fakeAttr');
        $relations = array();

        $attribute = new \Skovachev\Fakefactory\Model\Blueprint\Attribute('attr', 'integer');
        $idAttr = new \Skovachev\Fakefactory\Model\Blueprint\Attribute('id', 'integer');
        $modelAttributes = array($attribute, $idAttr);

        $buildOptions = array(
            'generate_id' => true,
            'override_attributes' => array(),
            'with' => array(),
            'exclude_attributes' => array(),
        );

        $relatedTo = array();

        $factory = Mockery::mock('Skovachev\Fakefactory\Factory')->makePartial();
        $factory->setBuildOptions($buildOptions);
        $faker = Mockery::mock('Skovachev\Fakefactory\Faker');
        $faker->shouldReceive('setClassBlueprint')->once();
        $faker->shouldReceive('fakeAttributes')->once()->andReturn($attributes);
        $faker->shouldReceive('fakeRelations')->once()->andReturn($relations);
        $faker->shouldReceive('getRelatedTo')->once()->andReturn($relatedTo);

        $factory->shouldReceive('getClassFaker')->with($class)->once()->andReturn($faker);
        
        $factory->setModelManager($this->modelManager);

        $this->modelManager->shouldReceive('isModelClass')->andReturn(true);
        $this->modelManager->shouldReceive('getAttributesForClass')->once()->with($class)->andReturn($modelAttributes);
        $this->modelManager->shouldReceive('getRelationsForClass')->once()->with($class, $relatedTo, array())->andReturn(array());

        $blueprint = $factory->makeBlueprint($class);

        $this->assertCount(2, $blueprint->getAttributes());
    }

    public function testExcludesAttributesIfSetInExcldesOption()
    {
        $this->getFactory();

        $class = 'DummyFakerClass';
        $attributes = array('attr' => 'fakeAttr');
        $relations = array();

        $attribute = new \Skovachev\Fakefactory\Model\Blueprint\Attribute('attr', 'integer');
        $idAttr = new \Skovachev\Fakefactory\Model\Blueprint\Attribute('id', 'integer');
        $modelAttributes = array($attribute, $idAttr);

        $buildOptions = array(
            'generate_id' => false,
            'override_attributes' => array(),
            'with' => array(),
            'exclude_attributes' => array('attr'),
        );

        $relatedTo = array();

        $factory = Mockery::mock('Skovachev\Fakefactory\Factory')->makePartial();
        $factory->setBuildOptions($buildOptions);
        $faker = Mockery::mock('Skovachev\Fakefactory\Faker');
        $faker->shouldReceive('setClassBlueprint')->once();
        $faker->shouldReceive('fakeAttributes')->once()->andReturn($attributes);
        $faker->shouldReceive('fakeRelations')->once()->andReturn($relations);
        $faker->shouldReceive('getRelatedTo')->once()->andReturn($relatedTo);

        $factory->shouldReceive('getClassFaker')->with($class)->once()->andReturn($faker);
        
        $factory->setModelManager($this->modelManager);

        $this->modelManager->shouldReceive('isModelClass')->andReturn(true);
        $this->modelManager->shouldReceive('getAttributesForClass')->once()->with($class)->andReturn($modelAttributes);
        $this->modelManager->shouldReceive('getRelationsForClass')->once()->with($class, $relatedTo, array())->andReturn(array());

        $blueprint = $factory->makeBlueprint($class);

        $this->assertCount(0, $blueprint->getAttributes());
    }

    public function testAppliesOverridesIfSet()
    {
        $this->getFactory();

        $class = 'DummyFakerClass';
        $attributes = array('attr' => 'fakeAttr');
        $relations = array();

        $attribute = new \Skovachev\Fakefactory\Model\Blueprint\Attribute('attr', 'integer');
        $modelAttributes = array($attribute);

        $buildOptions = array(
            'generate_id' => false,
            'override_attributes' => array('attr' => 'overridenValue'),
            'with' => array(),
            'exclude_attributes' => array(),
        );

        $relatedTo = array();

        $factory = Mockery::mock('Skovachev\Fakefactory\Factory')->makePartial();
        $factory->setBuildOptions($buildOptions);
        $faker = Mockery::mock('Skovachev\Fakefactory\Faker');
        $faker->shouldReceive('setClassBlueprint')->once();
        $faker->shouldReceive('fakeAttributes')->once()->andReturn($attributes);
        $faker->shouldReceive('fakeRelations')->once()->andReturn($relations);
        $faker->shouldReceive('getRelatedTo')->once()->andReturn($relatedTo);

        $factory->shouldReceive('getClassFaker')->with($class)->once()->andReturn($faker);
        
        $factory->setModelManager($this->modelManager);

        $this->modelManager->shouldReceive('isModelClass')->andReturn(true);
        $this->modelManager->shouldReceive('getAttributesForClass')->once()->with($class)->andReturn($modelAttributes);
        $this->modelManager->shouldReceive('getRelationsForClass')->once()->with($class, $relatedTo, array())->andReturn(array());

        $blueprint = $factory->makeBlueprint($class);

        $this->assertCount(1, $blueprint->getAttributes());
        $firstAttr = $blueprint->getAttributes();
        $firstAttr = $firstAttr[0];
        $this->assertEquals($firstAttr, $attribute);
        $this->assertEquals($firstAttr->getValue(), 'overridenValue');
    }

    public function testLoadsAdditionalRelationsBasedOnWithOption()
    {
        $this->getFactory();

        $class = 'DummyFakerClass';
        $attributes = array();
        $relations = array('foo' => 'fakeRel');

        $relation = new \Skovachev\Fakefactory\Model\Blueprint\Relation('foo', 'BelongsTo', 'FooClass', 'foo_id');
        $modelRelations = array($relation);

        $buildOptions = array(
            'generate_id' => false,
            'override_attributes' => array(),
            'with' => array('foo'),
            'exclude_attributes' => array(),
        );

        $relatedTo = array('foo');

        $factory = Mockery::mock('Skovachev\Fakefactory\Factory')->makePartial();
        $factory->setBuildOptions($buildOptions);
        $faker = Mockery::mock('Skovachev\Fakefactory\Faker');
        $faker->shouldReceive('setClassBlueprint')->once();
        $faker->shouldReceive('fakeAttributes')->once()->andReturn($attributes);
        $faker->shouldReceive('fakeRelations')->once()->andReturn($relations);
        $faker->shouldReceive('getRelatedTo')->once()->andReturn($relatedTo);

        $factory->shouldReceive('getClassFaker')->with($class)->once()->andReturn($faker);
        
        $factory->setModelManager($this->modelManager);

        $this->modelManager->shouldReceive('isModelClass')->andReturn(true);
        $this->modelManager->shouldReceive('getAttributesForClass')->once()->with($class)->andReturn(array());
        $this->modelManager->shouldReceive('getRelationsForClass')->once()->with($class, $relatedTo, array('foo'))->andReturn($modelRelations);

        $blueprint = $factory->makeBlueprint($class);

        $this->assertCount(1, $blueprint->getRelations());
        $firstRel = $blueprint->getRelations();
        $firstRel = $firstRel[0];
        $this->assertEquals($firstRel, $relation);
        $this->assertEquals($firstRel->getValue(), 'fakeRel');
    }

    public function testMakeBlueprintNotModelClass()
    {
        $this->getFactory();

        $class = 'DummyFakerClass';
        $attributes = array('attr' => 'fakeAttr');
        $relations = array('rel' => 'fakeRel');

        $buildOptions = array(
            'generate_id' => false,
            'override_attributes' => array(),
            'with' => array(),
            'exclude_attributes' => array(),
        );

        $factory = Mockery::mock('Skovachev\Fakefactory\Factory')->makePartial();
        $factory->setBuildOptions($buildOptions);
        $faker = Mockery::mock('Skovachev\Fakefactory\Faker');
        $faker->shouldReceive('setClassBlueprint')->once();
        $faker->shouldReceive('fakeAttributes')->once()->andReturn($attributes);
        $faker->shouldReceive('fakeRelations')->once()->andReturn($relations);

        $factory->shouldReceive('getClassFaker')->with($class)->once()->andReturn($faker);
        
        $factory->setModelManager($this->modelManager);

        $this->modelManager->shouldReceive('isModelClass')->andReturn(false);

        $blueprint = $factory->makeBlueprint($class);

        $this->assertEquals($blueprint->getAttributes(), array());
        $this->assertEquals($blueprint->getRelations(), array());
    }

    public function testRegisterClassFakerWithClosureAndArray()
    {
        $factory = $this->getFactory();
        $rules = array('foobar' => 'fooRule');
        $fakerMock = Mockery::mock('Skovachev\Fakefactory\Faker');
        $fakerMock->shouldReceive('mergeFakingRules')->with($rules);

        $this->reflector->shouldReceive('instantiate')->with('FooFaker', 'DummyFakerClass')->once()->andReturn($fakerMock);

        $factory->registerClassFaker('DummyFakerClass', function() use ($rules){
            return $rules;
        });
    }

    public function testRegisterClassFakerWithClassName()
    {
        $factory = $this->getFactory();

        $this->reflector->shouldReceive('instantiate')->with('FooFaker', 'DummyFakerClass')->once();

        $factory->registerClassFaker('DummyFakerClass', 'FooFaker');
    }

    public function testMakeAndCreate()
    {
        $factory = Mockery::mock('Skovachev\Fakefactory\Factory')->makePartial();
        $this->getFactory($factory);

        $class = 'foo';
        $savedModel = 'saved';
        $modelMock = Mockery::mock();

        $blueprintMock = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $blueprintMock->shouldReceive('getClass')->andReturn($class);
        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attributes = array($attribute);
        $relation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation');
        $relations = array($relation);
        $relation->shouldReceive('applyToModelAndContainedValue')->with($modelMock)->once();
        $blueprintMock->shouldReceive('getAttributes')->andReturn($attributes);
        $blueprintMock->shouldReceive('getRelations')->andReturn($relations);

        $this->reflector->shouldReceive('instantiate')->once()->andReturn($modelMock);

        $this->modelManager->shouldReceive('setAttribute')->with($modelMock, $attribute);
        $this->modelManager->shouldReceive('setRelation')->with($modelMock, $relation);
        $this->modelManager->shouldReceive('saveModel')->with($modelMock, $blueprintMock)->andReturn($savedModel);

        $factory->shouldReceive('makeBlueprint')->with($class)->once()->andReturn($blueprintMock);

        $result = $factory->create($class);

        $this->assertEquals($result, $savedModel);
    }

}