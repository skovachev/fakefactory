<?php namespace Skovachev\Fakefactory;

use Skovachev\Fakefactory\Facade as FakeFactory;
use Skovachev\Fakefactory\Exceptions\InvalidFactoryConfigurationException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * Creates a model blueprint and fills it with fake data
 * 
 * @package skovachev/fakefactory
 */
class Faker {

    /**
     * Class being faked
     * 
     * @var string
     */
    protected $class;

    /**
     * Blueprint class to hold fake class information
     * 
     * @var Skovachev\Fakefactory\Model\Blueprint\Blueprint
     */
    protected $blueprint;

    /**
     * Class used to manage and parse faker rules
     * 
     * @var Skovachev\Fakefactory\Faker\RulesManager
     */
    protected $rulesManager;

    /**
     * Faker class to generate fake data
     * 
     * @var Faker\Generator
     */
    protected $f;

    /**
     * A set of custom user-defined faker rules
     * 
     * @var array
     */
    protected $attributes = array();

    /**
     * Contains the names of relations that don't have a key on the model's table
     * 
     * @var array
     */
    protected $relatedTo = array();

    /**
     * Determines what relations are to be faked by default
     * 
     * @var array
     */
    protected $with = array();

    /**
     * All faker rules for the current class
     * 
     * @var array
     */
    protected $rules = array();

    /**
     * @param string $class 
     * @param Skovachev\Fakefactory\Faker\RulesManager $rulesManager 
     * @param Faker\Generator $fakeGenerator 
     */
    public function __construct($class, $rulesManager = null, $fakeGenerator = null)
    {
        $this->class = $class;
        $this->rulesManager = $rulesManager ?: App::make('fakefactory.rules.manager');
        $this->f = $fakeGenerator ?: App::make('fakefactory.generator');

        // load rules from faker class
        $this->mergeFakingRules($this->attributes);
    }

    /**
     * @return array
     */
    public function getRelatedTo()
    {
        return $this->relatedTo;
    }

    /**
     * Get faker rules
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Get all mandatory relations that need to be faked
     * @return array
     */
    public function getMandatoryRelations()
    {
        return $this->with;
    }

    /**
     * Sets the current blueprint and parses any faker rules it may contain
     * 
     * @param Skovachev\Fakefactory\Model\Blueprint\Blueprint $blueprint 
     */
    public function setClassBlueprint($blueprint)
    {
        $this->blueprint = $blueprint;

        // generate rules from attrs and rels
        $this->examineModelFields($blueprint->getAttributes());
    }

    /**
     * Generates fake attributes based on the current bluprint
     * 
     * @return array
     */
    public function fakeAttributes()
    {
        $attributes = array();
        foreach ($this->blueprint->getAttributes() as $attribute) {
            $fakingRule = array_get($this->rules, $attribute->getName());

            // if no rule exists set NULL
            if (is_null($fakingRule))
            {
                $attributes[$attribute->getName()] = null;
            }
            // if rules is set to custom -> look for a value generator method in faker class
            else if ($fakingRule->isCustom())
            {
                $attributeName = $attribute->getName();
                $methodName = $this->getValueGeneratorMethodName($attributeName);
                if (!ff_method_exists($this, $methodName))
                {
                    throw new InvalidFactoryConfigurationException("Attribute '$attributeName' has a custom faking rule but it's Faker class (" . get_class($this) . ") is missing the $methodName method");
                }
                // run faker method to get fake value
                $attributes[$attributeName] = ff_call_user_func(array($this, $methodName), $this->f);
            }
            else
            // value is a valid faker rule
            {
                $attributes[$attribute->getName()] = $this->fakeFieldValue($fakingRule);
            }
        }
        return $attributes;
    }

    /**
     * Generates fake relations based on the current blueprint
     * 
     * @param array $overrides 
     * @return array
     */
    public function fakeRelations($overrides = array())
    {
        $relationships = array();
        foreach ($this->blueprint->getRelations() as $relation) {

            $relatedObject = FakeFactory::make($relation->getRelatedClassName(), array_get($overrides, $relation->getName(), array()));

            $relationships[$relation->getName()] = $relation->isToManyRelation() ? array($relatedObject) : $relatedObject;
        }
        return $relationships;
    }

    /**
     * Load addition faker rules for an attributes array
     * 
     * @param array $attributes 
     */
    protected function examineModelFields($attributes)
    {
        foreach($attributes as $attribute)
        {
            // add field rule only if user hasn't already defined it
            if (!$this->userDefinedFakingRuleExists($attribute->getName()))
            {
                // try to create faking rule for model field
                $fakingRule = $this->rulesManager->createRuleForAttribute($attribute);
                if (!is_null($fakingRule))
                {
                    $this->rules[$attribute->getName()] = $fakingRule;
                }
            }
        }
    }

    /**
     * Add faking rules to this faker instance
     * 
     * @param array $fields 
     */
    public function mergeFakingRules($fields)
    {
        foreach ($fields as $fieldName => $fakingRule) {
            $this->rules[$fieldName] = $this->rulesManager->parseAttributeRule($fieldName, $fakingRule);
        }
    }

    /**
     * Fenerate fake value using faker library
     * @param Skovachev\Fakefactory\Faker\Rule $rule 
     * @return mixed
     */
    protected function fakeFieldValue($rule)
    {
        $methodName = $rule->getMethodName();

        if ($rule->hasArguments())
        {
            return call_user_func_array(array($this->f, $methodName), $rule->getArguments());
        }
        else
        {
            return $this->f->$methodName;
        }
    }

    /**
     * Check if user has defined a custom rule for a field
     * @param string $fieldName 
     * @return boolean
     */
    protected function userDefinedFakingRuleExists($fieldName)
    {
        $methodName = $this->getValueGeneratorMethodName($fieldName);
        return isset($this->rules[$fieldName]) || method_exists($this, $methodName);
    }

    /**
     * Get the custom generator method name
     * @param string $fieldName 
     * @return string
     */
    protected function getValueGeneratorMethodName($fieldName)
    {
        return 'get' . Str::studly($fieldName) . 'FakeValue';
    }

}