<?php namespace Skovachev\Fakefactory;

use Skovachev\Fakefactory\Model\ModelManager;
use Skovachev\Fakefactory\Model\Reflector;
use Skovachev\Fakefactory\Model\Blueprint\Blueprint;
use App;

/**
 * Creates models containing fake data
 * 
 * @package skovachev/fakefactory
 */
class Factory 
{
    protected $defaultFakerClass = 'Skovachev\Fakefactory\Faker';

    /**
     * A cache with faker classes
     * 
     * @var array
     */
    protected static $fakers = array();

    /**
     * Build options
     * 
     * @var array
     */
    protected $buildOptions = array();

    /**
     * Handles model metadata
     * 
     * @var Skovachev\Fakefactory\Model\ModelManager
     */
    protected $model;

    /**
     * Handles instatiation of models and other reflection tasks
     * 
     * @var Skovachev\Fakefactory\Model\Reflector
     */
    protected $reflector;

    /**
     * @param Skovachev\Fakefactory\Model\ModelManager $manager 
     * @param Skovachev\Fakefactory\Model\Reflector $reflector 
     */
    public function __construct(ModelManager $manager, Reflector $reflector = null)
    {
        $this->model = $manager;
        $this->reflector = $reflector ?: App::make('Skovachev\Fakefactory\Model\Reflector');
    }

    /**
     * @param Skovachev\Fakefactory\Model\ModelManager $modelManager 
     */
    public function setModelManager($modelManager)
    {
        $this->model = $modelManager;
    }

    /**
     * @param Skovachev\Fakefactory\Model\Reflector $reflector 
     */
    public function setReflector($reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     * Set build options for the build process
     * 
     * @param array $options 
     */
    public function setBuildOptions($options = array())
    {
        $this->buildOptions = $options;
    }

    /**
     * Clears build options after a successful build
     */
    protected function clearBuildOptions()
    {
        $this->setBuildOptions(array());
    }

    /**
     * Returns a specific build option
     * 
     * @param string $key 
     * @return mixed
     */
    protected function getBuildOption($key, $default = null)
    {
        return array_get($this->buildOptions, $key, $default);
    }

    /**
     * Creates a Blueprint for a class
     * 
     * @param string $class 
     * @param boolean $asRelationship if set the factory will not explore further nested relations
     * @return Skovachev\Fakefactory\Model\Blueprint\Blueprint
     */
    public function makeBlueprint($class, $asRelationship = false)
    {
        $overrides = $this->getBuildOption('override_attributes', array());
        $excludeAttributes = $this->getBuildOption('exclude_attributes', array());
        $overrideRules = $this->getBuildOption('override_rules', array());

        if ($this->getBuildOption('generate_id') === false)
        {
            $excludeAttributes[] = 'id';
        }

        // get faker class
        $faker = $this->getClassFaker($class, $overrideRules);
        $blueprint = new Blueprint($class);

        // if is model class -> try to extract information from model / database
        if ($this->model->isModelClass($class))
        {
            // extract fields
            $attributes = $this->model->getAttributesForClass($class);

            // exclude attributes from generation
            $attributes = array_filter($attributes, function($attribute) use ($excludeAttributes)
            {
                return !in_array($attribute->getName(), $excludeAttributes);
            });

            // merge fields from model
            $blueprint->mergeAttributes($attributes);

            // load relationship fields only if obj being built is not part of a relation
            if (!$asRelationship)
            {
                // extract relationships from model / db
                $relations = $this->model->getRelationsForClass($class, $faker->getRelatedTo(), $this->getBuildOption('with'));

                // add relationship data to faker
                $blueprint->mergeRelations($relations);
            }
        }

        $faker->setClassBlueprint($blueprint);

        // generate fake attributes
        $attributes = $faker->fakeAttributes();

        // generate fake relations
        $relations = $asRelationship ? array() : $faker->fakeRelations($this->extractRelationOverrides($overrides));

        $blueprint->initializeAttributes($attributes);
        $blueprint->initializeRelations($relations);

        // apply overrides for fields
        if (!empty($overrides))
        {
            $attributeOverrides = $this->extractAttributeOverrides($overrides);
            $blueprint->initializeAttributes($attributeOverrides);
        }

        return $blueprint;
    }

    /**
     * Extracts attribute overrides from build option
     * 
     * @param array $overrides 
     * @return array
     */
    protected function extractAttributeOverrides($overrides)
    {
        return array_filter($overrides, function($value){
            return !is_associative_array($value);
        });
    }

    /**
     * Extracts relation overrides from build options
     * 
     * @param array $overrides 
     * @return array
     */
    protected function extractRelationOverrides($overrides)
    {
        return array_filter($overrides, function($value){
            return is_associative_array($value);
        });
    }

    /**
     * Applies faker rule overrides to blueprint
     * 
     * @param Skovachev\Fakefactory\Model\Blueprint\Blueprint &$blueprint 
     * @param array $overrides 
     */
    protected function applyOverrides(&$blueprint, $overrides = array())
    {
        foreach ($overrides as $field => $value) {
            $object->$field = $value;
        }
    }

    /**
     * Returns an existing class name for use in the build process
     * 
     * @param string $class 
     * @return string
     */
    protected function getClass($class)
    {
        if (!class_exists($class))
        {
            return 'stdClass';
        }
        return $class;
    }

    /**
     * Get the Faker object for a specific class name
     * 
     * @param string $class 
     * @return Skovachev\Fakefactory\Faker
     */
    public function getClassFaker($class, $customRules = array())
    {
        $faker = null;

        // try to get faker class for from source class
        if (isset($class::$fakerClass))
        {
            $faker = $class::$fakerClass;
        }

        // else check if a custom binding was registered
        if (is_null($faker) && isset(static::$fakers[$class]) && !is_null(static::$fakers[$class]))
        {
            $faker = static::$fakers[$class];
        }

        // else create default faker
        if (is_null($faker))
        {
            $faker = $this->defaultFakerClass;
        }

        $faker = $this->reflector->instantiate($faker, $this->getClass($class));

        static::$fakers[$class] = $faker;

        // add drop in faking rules
        if (!empty($customRules))
        {
            $faker->mergeFakingRules($customRules);
        }

        return $faker;
    }

    /**
     * Register a Faker object for a specific class name
     * 
     * @param string $class 
     * @param string, array or Closure returning one of these $faker 
     */
    public function registerClassFaker($class, $faker)
    {
        $realClass = $this->getClass($class);

        // if we have a closure for faker creation -> run it
        if ($faker instanceof \Closure)
        {
            $faker = $faker();
        }

        // faker class is set
        if (is_string($faker))
        {
            $faker = $this->reflector->instantiate($faker, $realClass);
        }
        // we got a set of faker rules instead of faker class
        else if (is_array($faker))
        {
            $fakingRules = $faker;

            // try to find a suitable faker class
            $faker = $this->getClassFaker($class);

            // marge passed faking rules
            $faker->mergeFakingRules($fakingRules);
        }
        else if (!($faker instanceof Faker))
        {
            throw new \InvalidArgumentException('Second argument must be a Class String, Array, Faker object or a closure returning one of these');
        }

        static::$fakers[$class] = $faker;
    }

    /**
     * Create a fake model instance without saving it
     * 
     * @param string $class 
     * @param boolean $asRelationship 
     * @return object
     */
    public function make($class, $asRelationship = false)
    {
        $blueprint = $this->makeBlueprint($class, $asRelationship);
        return $this->makeFromBlueprint($blueprint);
    }

    /**
     * Create a fake model instance from Blueprint
     * 
     * @param Skovachev\Fakefactory\Model\Blueprint\Blueprint $blueprint 
     * @return object
     */
    protected function makeFromBlueprint($blueprint)
    {
        $modelClass = $blueprint->getClass();
        $model = $this->reflector->instantiate($modelClass);

        foreach ($blueprint->getAttributes() as $attribute) {
            $this->model->setAttribute($model, $attribute);
        }

        foreach ($blueprint->getRelations() as $relation) {
            $relation->applyToModelAndContainedValue($model);
            $this->model->setRelation($model, $relation);
        }

        $this->clearBuildOptions();

        return $model;
    }

    /**
     * Create a fake model instance and save it in the database
     * 
     * @param string $class 
     * @return object
     */
    public function create($class)
    {
        $blueprint = $this->makeBlueprint($class);

        // make and save model
        $model = $this->makeFromBlueprint($blueprint);

        // movel to model
        $model = $this->model->saveModel($model, $blueprint);

        return $model;
    }
}