<?php namespace Skovachev\Fakefactory;

use Illuminate\Support\ServiceProvider;

class FakefactoryServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// register model manager
		$this->app->bind('fakefactory.model.manager', function($app){
			return new \Skovachev\Fakefactory\Model\ModelManager(
				$app->make('Skovachev\Fakefactory\Model\DatabaseManager'),
				$app->make('Skovachev\Fakefactory\Model\Reflector')
			);
		});

		// register factory
		$this->app->bind('fakefactory', function($app){
			return new Factory($app->make('fakefactory.model.manager'));
		});

		// register fake data generator
		$this->app->bind('fakefactory.generator', function($app){
			return \Faker\Factory::create();
		});

		// register build query
		$this->app->bind('fakefactory.query', function($app){
			return $app->make('Skovachev\Fakefactory\Build\Query');
		});

		// register rules manager
		$this->app['fakefactory.rules.manager'] = $this->app->share(function($app){
			return $app->make('Skovachev\Fakefactory\Faker\RulesManager');
		});	
	}

	public function boot()
	{
		$this->package('skovachev/fakefactory', 'fakefactory');

		include __DIR__ . '/../../helpers.php';
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('fakefactory', 'fakefactory.query');
	}

}