<?php

namespace Jiaxincui\RequestFilter;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Jiaxincui\RequestFilter\BaseFilter;
use Jiaxincui\RequestFilter\Console\FilterMakeCommand;

class FilterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FilterMakeCommand::class,
            ]);
        }

        $request = $this->app->make('request');
        BaseFilter::setQuery($request->isMethod('get') ? $request->query() : []);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
