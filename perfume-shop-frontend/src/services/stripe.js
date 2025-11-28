import api from './api';
import { loadStripe } from '@stripe/stripe-js';

const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY || 'pk_test_your_stripe_key_here');

export const stripeService = {
  getStripe: async () => {
    return await stripePromise;
  },

  createCheckoutSession: async (cartItems, shippingAddressId) => {
    const response = await api.post('/v1/stripe/create-checkout-session', {
      cart_items: cartItems,
      shipping_address_id: shippingAddressId,
    });
    return response.data;
  },

  createPaymentIntent: async (shippingAddressId) => {
    const response = await api.post('/v1/checkout/create-intent', {
      shipping_address_id: shippingAddressId,
    });
    return response.data;
  },

  updatePaymentIntent: async (paymentIntentId, shippingAddressId) => {
    const response = await api.post('/v1/checkout/update-intent', {
      payment_intent_id: paymentIntentId,
      shipping_address_id: shippingAddressId,
    });
    return response.data;
  },
};

