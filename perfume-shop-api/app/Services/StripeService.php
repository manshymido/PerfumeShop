<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * Create a payment intent.
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', array $metadata = []): PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'amount' => (int)($amount * 100), // Convert to cents
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    /**
     * Retrieve a payment intent.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Update a payment intent amount.
     */
    public function updatePaymentIntent(string $paymentIntentId, float $amount, array $metadata = []): PaymentIntent
    {
        $params = [
            'amount' => (int)($amount * 100), // Convert to cents
        ];

        if (!empty($metadata)) {
            $params['metadata'] = $metadata;
        }

        return $this->stripe->paymentIntents->update($paymentIntentId, $params);
    }

    /**
     * Confirm a payment intent.
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethodId): PaymentIntent
    {
        return $this->stripe->paymentIntents->confirm([
            'id' => $paymentIntentId,
            'payment_method' => $paymentMethodId,
        ]);
    }

    /**
     * Create a refund.
     */
    public function createRefund(string $paymentIntentId, ?float $amount = null, array $metadata = []): \Stripe\Refund
    {
        $params = [
            'payment_intent' => $paymentIntentId,
            'metadata' => $metadata,
        ];

        if ($amount !== null) {
            $params['amount'] = (int)($amount * 100); // Convert to cents
        }

        return $this->stripe->refunds->create($params);
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): ?\Stripe\Event
    {
        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}

