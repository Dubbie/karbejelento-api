<?php

namespace App\Providers;

use App\Services\PaginationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define the 'advancedPaginate' macro on the Eloquent Builder.
        Builder::macro('advancedPaginate', function (Request $request, array $options) {
            /** @var \Illuminate\Database\Eloquent\Builder $this */

            // Call our dedicated service to perform the pagination.
            // The '$this' context inside the macro is the Builder instance itself.
            return PaginationService::paginate($this, $request, $options);
        });
    }
}
