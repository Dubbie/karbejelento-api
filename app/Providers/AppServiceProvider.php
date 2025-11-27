<?php

namespace App\Providers;

use App\Services\PaginationService;
use App\Services\ReportService;
use App\Services\ReportStatusTransitionService;
use App\Services\ReportStatusTransitions\Rules\CloseReportAsDuplicateRule;
use App\Services\ReportStatusTransitions\Rules\CloseReportWithPaymentRule;
use App\Services\ReportStatusTransitions\Rules\RequireDamageIdForUnderAdministrationRule;
use App\Services\ReportStatusTransitions\Rules\SendDocumentRequestEmailRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReportStatusTransitionService::class, function ($app) {
            return new ReportStatusTransitionService(
                $app->make(ReportService::class),
                [
                    $app->make(RequireDamageIdForUnderAdministrationRule::class),
                    $app->make(SendDocumentRequestEmailRule::class),
                    $app->make(CloseReportWithPaymentRule::class),
                    $app->make(CloseReportAsDuplicateRule::class),
                ]
            );
        });
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

        JsonResource::withoutWrapping();
    }
}
