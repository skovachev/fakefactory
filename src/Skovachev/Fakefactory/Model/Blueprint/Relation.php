<?php namespace Skovachev\Fakefactory\Model\Blueprint;

use Skovachev\Fakefactory\Exceptions\UnsupportedFeatureException;

class Relation extends Item
{
    protected $type;
    protected $relatedClass;
    protected $foreignKey;

    public function __construct($key, $type, $relatedClass, $foreignKey, $value = null)
    {
        parent::__construct($key, $value);
        $this->type = $type;
        $this->relatedClass = $relatedClass;
        $this->foreignKey = $foreignKey;
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRelatedClassName()
    {
        return $this->relatedClass;
    }

    public function isToManyRelation()
    {
        return $this->type == 'HasMany' || $this->type == 'BelongsToMany';
    }

    public function savedBeforeModel()
    {
        return $this->type == 'BelongsTo' || $this->type == 'BelongsToMany';
    }

    public function applyTo(&$relatedModelAttributes, &$modelAttributes)
    {
        $foreignKey = $this->getForeignKey();

        if ($this->type == 'HasOne' || $this->type == 'HasMany')
        {
            $this->setAttribute($relatedModelAttributes, $foreignKey, $this->getAttribute($modelAttributes, 'id'));
        }
        else if ($this->type == 'BelongsTo' || $this->type == 'BelongsToMany')
        {
            $this->setAttribute($modelAttributes, $foreignKey, $this->getAttribute($relatedModelAttributes, 'id'));
        }
        else
        {
            throw new UnsupportedFeatureException("Fakefactory does not support relations of type '" . $this->type . "'");
        }
    }

    public function applyToModelAndContainedValue(&$model)
    {
        return $this->applyTo($this, $model);
    }

    protected function setAttribute(&$model, $key, $value)
    {
        if (is_associative_array($model))
        {
            $model[$key] = $value;
        }
        else if ($model instanceof Relation)
        {
            $relationValue = $model->getValue();
            $this->setAttribute($relationValue, $key, $value);
            $model->setValue($relationValue);
        }
        // if toMany relation -> go into the array and set value
        else if (is_array($model) && !is_associative_array($model))
        {
            return $this->setAttribute($model[0], $key, $value);
        }
        else
        {
            $model->$key = $value;
        }
    }

    protected function getAttribute($model, $key)
    {
        if ($model instanceof Relation)
        {
            $model = $model->getValue();
        }
        return is_associative_array($model) ? $model[$key] : $model->$key;
    }

}