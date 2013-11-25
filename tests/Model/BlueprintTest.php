<?php

use Skovachev\Fakefactory\Model\Blueprint\Blueprint;

class BluePrintTest extends TestCase {

    public function testMergeAttributes()
    {
        $blueprint = new Blueprint('foo');
        $attributes1 = array('foo');
        $attributes2 = array('bar');

        $blueprint->mergeAttributes($attributes1);
        $blueprint->mergeAttributes($attributes2);

        $this->assertEquals($blueprint->getAttributes(), array('foo', 'bar'));
    }

    public function testMergeRelations()
    {
        $blueprint = new Blueprint('foo');
        $relations1 = array('foo');
        $relations2 = array('bar');

        $blueprint->mergeRelations($relations1);
        $blueprint->mergeRelations($relations2);

        $this->assertEquals($blueprint->getRelations(), array('foo', 'bar'));
    }

    public function testInitializeAttributesAndUpdateAttribute()
    {
        $blueprint = new Blueprint('foo');
        $attributes = array('foo' => 'bar');

        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attribute->shouldReceive('getName')->twice()->andReturn('foo');
        $attribute->shouldReceive('setValue')->once()->with('bar');
        $blueprint->mergeAttributes(array($attribute));

        $blueprint->initializeAttributes($attributes);
    }

    public function testInitializeAttributesAndNotUpdateAttribute()
    {
        $blueprint = new Blueprint('foo');
        $attributes = array('baz' => 'bar');

        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attribute->shouldReceive('getName')->once()->andReturn('foo');
        $blueprint->mergeAttributes(array($attribute));

        $blueprint->initializeAttributes($attributes);
    }

    public function testInitializeRelations()
    {
        $blueprint = new Blueprint('foo');
        $relations = array('bar' => 'bar');

        $relation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation');
        $relation->shouldReceive('getName')->once()->andReturn('foo');
        $blueprint->mergeRelations(array($relation));

        $blueprint->initializeRelations($relations);
    }
    
}