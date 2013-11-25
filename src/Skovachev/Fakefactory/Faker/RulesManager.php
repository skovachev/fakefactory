<?php namespace Skovachev\Fakefactory\Faker;

use Config;

class RulesManager
{
    protected $fakingRulesForDatabaseTypes;
    protected $fakingRulesForSpecialFields;

    public function __construct($config = null)
    {
        $config = $config ?: Config::getFacadeRoot();
        $this->fakingRulesForDatabaseTypes = $config->get('fakefactory::database_type_rules');
        $this->fakingRulesForSpecialFields = $config->get('fakefactory::special_field_rules');
    }

    // get preset faking rule based on field data
    public function createRuleForAttribute($attribute)
    {
        $name = $attribute->getName();
        $type = $attribute->getType();

        // check for name
        if (in_array($name, array_keys($this->fakingRulesForSpecialFields)))
        {
            return $this->parseAttributeRule($name, $this->fakingRulesForSpecialFields[$name]);
        }

        // check for type
        if (in_array($type, array_keys($this->fakingRulesForDatabaseTypes)))
        {
            return $this->parseAttributeRule($name, $this->fakingRulesForDatabaseTypes[$type]);
        }

        return null;
    }

    public function parseAttributeRule($attributeName, $ruleData)
    {
        if ($ruleData instanceof Rule)
        {
            return $ruleData;
        }

        // make sure we have an array for further processing
        $ruleParts = is_string($ruleData) ? array_values(explode('|', $ruleData)) : $ruleData;
        $methodName = array_shift($ruleParts);

        return new Rule($methodName, $ruleParts);
    }
}