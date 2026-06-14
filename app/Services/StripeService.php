<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\PaymentIntent;
use Stripe\Event;
use Stripe\Webhook;

class StripeService
{
    private StripeClient $stripe;
    private string $currency;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        $this->currency = config('services.stripe.currency', 'egp');
    }

    /**
     * Create a PaymentIntent for the given amount.
     * Stripe expects amount in smallest currency unit (piasters for EGP).
     */
    public function createPaymentIntent(float $amount, array $metadata = []): PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'amount'   => (int) round($amount * 100), // EGP → piasters
            'currency' => $this->currency,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Retrieve a PaymentIntent by ID.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Construct and verify a webhook event from the raw payload.
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): Event
    {
        return Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('services.stripe.webhook_secret')
        );
    }
}
