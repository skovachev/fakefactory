<?php

if ( ! function_exists('is_associative_array'))
{
    function is_associative_array($array) 
    {
        return !is_null($array) && is_array($array) && ((bool)count(array_filter(array_keys($array), 'is_string')));
    }
}

if ( ! function_exists('ff_method_exists'))
{
    function ff_method_exists($object, $method) 
    {
        return method_exists($object, $method);
    }
}

if ( ! function_exists('ff_call_user_func'))
{
    function ff_call_user_func() 
    {
        return call_user_func_array('call_user_func', func_get_args());
    }
}