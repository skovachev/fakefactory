<?php namespace Skovachev\Fakefactory\Model;

class Reflector
{
    public function instantiate()
    {
        $args = func_get_args();
        $class = array_shift($args);

        $reflection = new \ReflectionClass($class); 
        $instance = $reflection->newInstanceArgs($args); 

        return $instance;
    }

    public function methodExists($object, $method)
    {
        return method_exists($object, $method);
    }

    public function isModel($object)
    {
        return $object instanceof \Illuminate\Database\Eloquent\Model;
    }

    public function isRelation($object)
    {
        return $object instanceof \Illuminate\Database\Eloquent\Relations\Relation;
    }

    public function getClass($object)
    {
        return get_class($object);
    }
}