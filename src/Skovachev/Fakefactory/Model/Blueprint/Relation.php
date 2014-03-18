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

        if ($this->getType() == 'HasOne' || $this->getType() == 'HasMany')
        {
            $this->setAttribute($relatedModelAttributes, $foreignKey, $this->getAttribute($modelAttributes, 'id'));
        }
        else if ($this->getType() == 'BelongsTo' || $this->getType() == 'BelongsToMany')
        {
            $this->setAttribute($modelAttributes, $foreignKey, $this->getAttribute($relatedModelAttributes, 'id'));
        }
        else
        {
            throw new UnsupportedFeatureException("Fakefactory does not support relations of type '" . $this->getType() . "'");
        }
    }

    public function applyToModelAndContainedValue(&$model)
    {
        return $this->applyTo($this, $model);
    }

    protected function setAttribute(&$model, $key, $value, $isCollection = true)
    {
        // check if relation is a to many
        $isCollection = $isCollection && ($model instanceof \Illuminate\Support\Collection);
        if ($isCollection)
        {
            // need to convert \Illuminate\Support\Collection to array since pass be reference doesn't work on it
            $modelClass = get_class($model);
            $model = $model->toArray();
            foreach ($model as &$modelItem) {
                $this->setAttribute($modelItem, $key, $value);
            }
            $model = new $modelClass($model);
        }
        else
        {
            if (is_associative_array($model))
            {
                $model[$key] = $value;
            }
            else if ($model instanceof Relation)
            {
                $relationValue = $model->getValue();
                $this->setAttribute($relationValue, $key, $value, $model->isToManyRelation());

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