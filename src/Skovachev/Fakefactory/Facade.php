<?php namespace Skovachev\Fakefactory;

use Illuminate\Support\Facades\Facade as BaseFacade;
use App;

class Facade extends BaseFacade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'fakefactory.query'; }

    /**
     * Register a class faker or a set of faker rules for class
     * @param string $class
     * @param string, array or Closure returning one of these $faker 
     */
    public static function registerClassFaker($class, $faker)
    {
        $factory = App::make('fakefactory');
        $factory->registerClassFaker($class, $faker);
    }

}