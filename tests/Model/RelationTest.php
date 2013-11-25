<?php namespace Skovachev\Fakefactory\Model\Blueprint;

function is_associative_array()
{
    return true;
}

use TestCase;
use Mockery;
use Skovachev\Fakefactory\Model\Blueprint\Relation;

class RelationTest extends TestCase {

    public function testApplyToModelAndContainedValue()
    {
        $relation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation')->makePartial();
        $model = array('foo');
        $relation->shouldReceive('applyTo')->with($relation, $model)->once()->andReturn('bar');

        $result = $relation->applyToModelAndContainedValue($model);
        $this->assertEquals($result, 'bar');
    }

    public function testApplyToBelongsToRelation()
    {
        $relation = new Relation('foo', 'BelongsTo', 'bar', 'bar_id');
        $relatedModel = array('id' => 'baz');
        $model = array();

        $relation->applyTo($relatedModel, $model);

        $this->assertEquals($model, array('bar_id' => 'baz'));
        $this->assertEquals($relatedModel, array('id' => 'baz'));
    }

    public function testApplyToHasRelation()
    {
        $relation = new Relation('foo', 'HasOne', 'bar', 'bar_id');
        $model = array('id' => 'baz');
        $relatedModel = array();

        $relation->applyTo($relatedModel, $model);

        $this->assertEquals($relatedModel, array('bar_id' => 'baz'));
        $this->assertEquals($model, array('id' => 'baz'));
    }

    public function testApplyToRelationInsteadOfModel()
    {
        $relation = new Relation('foo', 'BelongsTo', 'bar', 'bar_id');
        $relatedModel = array('id' => 'baz');
        $relationModelRelation = new Relation('foo', 'hasOne', 'bar', 'bar_id');
        $relationModelRelation->setValue($relatedModel);
        $model = array();

        $relation->applyTo($relationModelRelation, $model);

        $this->assertEquals($model, array('bar_id' => 'baz'));
        $this->assertEquals($relationModelRelation->getValue(), array('id' => 'baz'));
    }
    
}