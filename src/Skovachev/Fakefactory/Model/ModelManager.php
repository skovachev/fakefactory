<?php namespace Skovachev\Fakefactory\Model;

use Skovachev\Fakefactory\Model\Blueprint\Attribute;
use Skovachev\Fakefactory\Model\Blueprint\Relation;

use Skovachev\Fakefactory\InvalidFactoryConfigurationException;

class ModelManager
{
    protected $schema;
    protected $reflector;

    protected static $fields = array();


    public function __construct(DatabaseManager $schema, Reflector $reflector)
    {
        $schema->registerTypeMapping('enum', 'string');
        $this->schema = $schema;
        $this->reflector = $reflector;
    }

    public function createModelInstance($class)
    {
        return $this->reflector->instantiate($class);
    }

    protected function getRelationObject($modelObject, $relationName)
    {
        if ($this->reflector->methodExists($modelObject, $relationName))
        {
            $relationObject = call_user_func(array($modelObject, $relationName));
            if ($this->isValidRelationObject($relationObject))
            {
                return $relationObject;
            }
        }

        return false;
    }

    protected function isValidRelationObject($relationObject)
    {
        return $this->reflector->isRelation($relationObject);
    }

    protected function getRelationClass($relationObject)
    {
        return class_basename($this->reflector->getClass($relationObject));
    }

    protected function getRelationRelatedClass($relationObject)
    {
        return $this->reflector->getClass($relationObject->getRelated());
    }

    protected function getRelationForeignKey($relationObject)
    {
        $foreignKey = null;
        $relationClassName = $this->getRelationClass($relationObject);

        if ($relationClassName == 'BelongsTo' || $relationClassName == 'BelongsToMany')
        {
            $foreignKey = $relationObject->getForeignKey();
        }
        else if ($relationClassName == 'HasOne' || $relationClassName == 'HasMany')
        {
            $foreignKey = $relationObject->getPlainForeignKey();
        }

        return $foreignKey;
    }

    protected function preloadDatabaseFieldsForClass($class)
    {
        $tableName = $this->getTableName($class);

        if (isset(static::$fields[$tableName]))
        {
            return static::$fields[$tableName];
        }

        $fields = array();

        $columns = $this->schema->listTableColumnsAsArray($tableName);

        foreach ($columns as $columnName => $columnType) {
            $fields[] = new Attribute($columnName, $columnType);
        }

        return static::$fields[$tableName] = $fields;
    }

    public function getAttributesForClass($class)
    {
        return $this->preloadDatabaseFieldsForClass($class);
    }

    public function isModelClass($class)
    {
        $instance = $this->createModelInstance($class);
        return $this->reflector->isModel($instance);
    }

    public function getRelationsForClass($class, $relatedTo = array(), $allowedRelations = array())
    {
        $fields = $this->preloadDatabaseFieldsForClass($class);
        $modelObject = $this->createModelInstance($class);
        $relationships = array();
        foreach ($fields as $field) {
            $relationshipKey = $this->hasForeignKey($field->getName());

            if ($relationshipKey && !empty($allowedRelations) && in_array($relationshipKey, $allowedRelations))
            {
                $relationships[] = $this->extractRelationshipData($modelObject, $relationshipKey);
            }
        }

        // get only allowed relatedTo relations
        $relatedTo = array_intersect($relatedTo, $allowedRelations);
        foreach ($relatedTo as $relationshipKey) {
            $relationships[] = $this->extractRelationshipData($modelObject, $relationshipKey);
        }
        return $relationships;
    }

    protected function extractRelationshipData($modelObject, $relationKey)
    {
        $relationshipClassName = null;
        $relatedClassName = null;
        $foreignKey = null;

        if ($relationObject = $this->getRelationObject($modelObject, $relationKey))
        {
            $relationshipClassName = $this->getRelationClass($relationObject);
            $relatedClassName = $this->getRelationRelatedClass($relationObject);
            $foreignKey = $this->getRelationForeignKey($relationObject);
        }

        return new Relation($relationKey, $relationshipClassName, $relatedClassName, $foreignKey);
    }

    protected function hasForeignKey($field)
    {
        // Do we need to create a relationship?
        // Look for a field, like author_id or author-id
        if (preg_match('/([A-z]+)[-_]id$/i', $field, $matches))
        {
            return $matches[1];
        }

        return false;
    }

    protected function getTableName($class)
    {
        $instance = $this->createModelInstance($class);
        return $instance->getTable();
    }

    public function setAttribute(&$model, $attribute)
    {
        $key = $attribute->getName();
        $model->$key = $attribute->getValue();
    }

    public function setRelation(&$model, $relation)
    {
        $value = $relation->getValue();
        if ($relation->isToManyRelation() && is_array($value) && !is_associative_array($value))
        {
            $value = new \Illuminate\Support\Collection($value);
        }
        $model->setRelation($relation->getName(), $value);
    }

    public function saveModel($model, $blueprint)
    {
        $relationshipKeys = array_map(function($relation){
            return $relation->getName();
        }, $blueprint->getRelations());

        $preSaveRelations = array_filter($blueprint->getRelations(), function($relation){
            return $relation->savedBeforeModel();
        });

        $afterSaveRelations = array_filter($blueprint->getRelations(), function($relation){
            return !$relation->savedBeforeModel();
        });

        foreach ($preSaveRelations as $relation) {
            $key = $relation->getName();
            $relationship = $model->$key;
            // set foreign keys
            $relationship->save();

            // load foreign keys after saving
            $relation->applyTo($relationship, $model);
        }

        $model->save();

        foreach ($afterSaveRelations as $relation) {
            $key = $relation->getName();
            $relationship = $model->$key;
            // set foreign keys after model saved
            $relation->applyTo($relationship, $model);
            
            $relationship->save();
        }

        if (! empty($relationshipKeys))
        {
            call_user_func_array(array($model, 'load'), $relationshipKeys);
        }

        return $model;
    }

    public function clearCachedFieldData()
    {
        static::$fields = array();
    }
}