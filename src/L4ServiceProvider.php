<?php 

namespace Hansvn\Vcalendar;
use Illuminate\Support\ServiceProvider;

class L4ServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('hansvn/vcalendar');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['vcalendar'] = $this->app->share(function($app)
		{
			$request = $app['request'];
			return new \Vcalendar($request);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
