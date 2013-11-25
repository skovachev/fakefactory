<?php

use Skovachev\Fakefactory\Model\ModelManager;

class ModelManagerTest extends TestCase {

    protected $reflector;
    protected $databaseManager;

    protected function getManager()
    {
        $databaseManager = Mockery::mock('Skovachev\Fakefactory\Model\DatabaseManager');
        $databaseManager->shouldReceive('registerTypeMapping')->once()->with('enum', 'string');
        $reflector = Mockery::mock('Skovachev\Fakefactory\Model\Reflector');

        $this->reflector = $reflector;
        $this->databaseManager = $databaseManager;

        $manager = new ModelManager($databaseManager, $reflector);

        return $manager;
    }

    public function testSetAttribute()
    {
        $manager = $this->getManager();

        $attribute = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Attribute');
        $attribute->shouldReceive('getName')->once()->andReturn('foo');
        $attribute->shouldReceive('getValue')->once()->andReturn('bar');

        $model = new stdClass;
        $manager->setAttribute($model, $attribute);

        $this->assertEquals($model->foo, 'bar');
    }

    public function testSetRelation()
    {
        $relationName = 'foo';
        $relationValue = 'bar';

        $databaseManager = Mockery::mock('Skovachev\Fakefactory\Model\DatabaseManager');
        $databaseManager->shouldReceive('registerTypeMapping')->once()->with('enum', 'string');
        $reflector = Mockery::mock('Skovachev\Fakefactory\Model\Reflector');
        $manager = new ModelManager($databaseManager, $reflector);

        $relation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation');
        $relation->shouldReceive('getName')->once()->andReturn($relationName);
        $relation->shouldReceive('getValue')->once()->andReturn($relationValue);
        $relation->shouldReceive('isToManyRelation')->once()->andReturn(false);

        $model = Mockery::mock();
        $model->shouldReceive('setRelation')->once()->with($relationName, $relationValue);

        $manager->setRelation($model, $relation);
    }

    public function testSaveModel()
    {
        $model = Mockery::mock();
        $model->shouldReceive('save')->once();
        $model->shouldReceive('load')->once()->with('preRel', 'postRel');
        $preRelModel = Mockery::mock();
        $preRelModel->shouldReceive('save')->once();
        $model->preRel = $preRelModel;
        $postRelModel = Mockery::mock();
        $postRelModel->shouldReceive('save')->once();
        $model->postRel = $postRelModel;

        $preRelation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation');
        $preRelation->shouldReceive('savedBeforeModel')->andReturn(true);
        $preRelation->shouldReceive('getName')->andReturn('preRel');
        $preRelation->shouldReceive('applyTo')->with($preRelModel, $model)->once();

        $postRelation = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Relation');
        $postRelation->shouldReceive('savedBeforeModel')->andReturn(false);
        $postRelation->shouldReceive('getName')->andReturn('postRel');
        $postRelation->shouldReceive('applyTo')->with($postRelModel, $model)->once();

        $blueprint = Mockery::mock('Skovachev\Fakefactory\Model\Blueprint\Blueprint');
        $blueprint->shouldReceive('getRelations')->andReturn(array($preRelation, $postRelation));

        $manager = $this->getManager();

        $model = $manager->saveModel($model, $blueprint);
    }

    protected function runGetRelationsForClassScenario($choices = array())
    {
        $externalRelationsAdded = array_get($choices, 'external_relations_added', false);
        $excludeRelations = array_get($choices, 'excluding_relations', false);

        $relationName = 'relation'; 
        $relationForeignKey = 'foreignKey'; 
        $relatedClassName = 'Testing\DummyModelClass';
        $relationClass = 'Illuminate\Database\Eloquent\Relations\BelongsTo';
        $relationType = 'BelongsTo';

        $externalRelationName = 'bar'; 
        $externalRelationForeignKey = 'externalForeignKey'; 
        $externalRelatedClassName = 'Testing\DummyModelClass2';
        $externalRelationClass = 'Illuminate\Database\Eloquent\Relations\BelongsTo';
        $externalRelationType = 'BelongsTo';

        $allowedRelations = $excludeRelations ? array($externalRelationName) : array($relationName, $externalRelationName);
        $relatedTo = $externalRelationsAdded ? array($externalRelationName) : array();

        $databaseManager = Mockery::mock('Skovachev\Fakefactory\Model\DatabaseManager');
        $databaseManager->shouldReceive('registerTypeMapping')->once()->with('enum', 'string');
        $databaseManager->shouldReceive('listTableColumnsAsArray')->once()->with('table')->andReturn(array($relationName . '_id' => 'bar'));

        $model = Mockery::mock();
        $model->shouldReceive('getTable')->andReturn('table');

        $reflector = Mockery::mock('Skovachev\Fakefactory\Model\Reflector');
        $reflector->shouldReceive('instantiate')->with('className')->andReturn($model);

        if (!$excludeRelations)
        {
            $relationRelatedObject = Mockery::mock();
            $relationObject = Mockery::mock();
            $relationObject->shouldReceive('getRelated')->once()->andReturn($relationRelatedObject);
            $relationObject->shouldReceive('getForeignKey')->once()->andReturn($relationForeignKey);

            $model->shouldReceive($relationName)->andReturn($relationObject);

            $reflector->shouldReceive('methodExists')->andReturn(true);
            $reflector->shouldReceive('isRelation')->andReturn(true);
            $reflector->shouldReceive('getClass')->with($relationObject)->andReturn($relationClass);
            $reflector->shouldReceive('getClass')->with($relationRelatedObject)->andReturn($relatedClassName);

            if ($externalRelationsAdded)
            {
                $externalRelationRelatedObject = Mockery::mock();
                $externalRelationObject = Mockery::mock();
                $externalRelationObject->shouldReceive('getRelated')->once()->andReturn($externalRelationRelatedObject);
                $externalRelationObject->shouldReceive('getForeignKey')->once()->andReturn($externalRelationForeignKey);

                $model->shouldReceive($externalRelationName)->andReturn($externalRelationObject);

                $reflector->shouldReceive('getClass')->with($externalRelationObject)->andReturn($externalRelationClass);
                $reflector->shouldReceive('getClass')->with($externalRelationRelatedObject)->andReturn($externalRelatedClassName);
            }
        }

        $manager = new ModelManager($databaseManager, $reflector);
        $manager->clearCachedFieldData();

        $relations = $manager->getRelationsForClass('className', $relatedTo, $allowedRelations);
        $expectedCount = 1;
        if ($excludeRelations)
        {
            $expectedCount = 0;
        }
        else if ($externalRelationsAdded)
        {
            $expectedCount = 2;
        }
        $this->assertCount($expectedCount, $relations);

        if (!$excludeRelations)
        {
            $relation = $relations[0];

            $this->assertInstanceOf('Skovachev\Fakefactory\Model\Blueprint\Relation', $relation);
            $this->assertEquals($relation->getName(), $relationName);
            $this->assertEquals($relation->getType(), $relationType);
            $this->assertEquals($relation->getRelatedClassName(), $relatedClassName);
            $this->assertEquals($relation->getForeignKey(), $relationForeignKey);

            if ($externalRelationsAdded)
            {
                $this->assertInstanceOf('Skovachev\Fakefactory\Model\Blueprint\Relation', $relations[1]);
                $this->assertEquals($relations[1]->getName(), $externalRelationName);
                $this->assertEquals($relations[1]->getType(), $externalRelationType);
                $this->assertEquals($relations[1]->getRelatedClassName(), $externalRelatedClassName);
                $this->assertEquals($relations[1]->getForeignKey(), $externalRelationForeignKey);
            }
        }
    }

    public function testGetRelationsForClass()
    {
        $this->runGetRelationsForClassScenario();
    }

    public function testGetExternalRelationsForClass()
    {
        $this->runGetRelationsForClassScenario(array(
            'external_relations_added' => true
        ));
    }

    public function testExcludeRelationsForClass()
    {
        $this->runGetRelationsForClassScenario(array(
            'excluding_relations' => true
        ));
    }

    public function testGetAttributesForClass()
    {
        $databaseManager = Mockery::mock('Skovachev\Fakefactory\Model\DatabaseManager');
        $databaseManager->shouldReceive('registerTypeMapping')->once()->with('enum', 'string');
        $databaseManager->shouldReceive('listTableColumnsAsArray')->once()->with('table')->andReturn(array('foo' => 'bar'));

        $model = Mockery::mock();
        $model->shouldReceive('getTable')->andReturn('table');

        $reflector = Mockery::mock('Skovachev\Fakefactory\Model\Reflector');
        $reflector->shouldReceive('instantiate')->with('foo')->andReturn($model);

        $manager = new ModelManager($databaseManager, $reflector);
        $manager->clearCachedFieldData();

        $attributes = $manager->getAttributesForClass('foo');

        $this->assertCount(1, $attributes);
        $this->assertInstanceOf('Skovachev\Fakefactory\Model\Blueprint\Attribute', $attributes[0]);
        $this->assertEquals($attributes[0]->getName(), 'foo');
        $this->assertEquals($attributes[0]->getType(), 'bar');
    }
    
}