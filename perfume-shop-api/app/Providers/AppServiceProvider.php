<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Review;
use App\Models\ShippingAddress;
use App\Policies\OrderPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ShippingAddressPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        Review::class => ReviewPolicy::class,
        ShippingAddress::class => ShippingAddressPolicy::class,
    ];

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
        //
    }
}
