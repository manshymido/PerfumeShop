import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  PaymentElement,
  useStripe,
  useElements,
} from '@stripe/react-stripe-js';
import { orderService } from '../services/orders';
import { stripeService } from '../services/stripe';
import { useCart } from '../context/CartContext';
import toast from 'react-hot-toast';

export default function CheckoutForm({ amount, shippingAddressId, paymentIntentId, onSuccess }) {
  const { loadCart } = useCart();
  const stripe = useStripe();
  const elements = useElements();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!stripe || !elements) {
      return;
    }

    setLoading(true);

    try {
      // Verify payment intent amount matches current cart total before confirming
      try {
        const updateResponse = await stripeService.updatePaymentIntent(paymentIntentId, shippingAddressId);
        if (updateResponse.success) {
          const paymentIntentAmount = updateResponse.data.amount;
          const amountDifference = Math.abs(paymentIntentAmount - amount);
          
          // If amount differs significantly, prevent payment and ask user to refresh
          if (amountDifference > 0.01) {
            toast.error(
              `Cart total has changed. Please refresh the page to update the payment amount.\nExpected: $${amount.toFixed(2)}, Payment Intent: $${paymentIntentAmount.toFixed(2)}`,
              { duration: 8000 }
            );
            setLoading(false);
            return;
          }
        }
      } catch (updateError) {
        console.error('Failed to verify payment intent:', updateError);
        toast.error('Failed to verify payment amount. Please refresh the page and try again.', { duration: 5000 });
        setLoading(false);
        return;
      }

      // Submit the form to collect payment details
      const { error: submitError } = await elements.submit();
      if (submitError) {
        throw submitError;
      }

      // Confirm payment with the existing payment intent
      const { error, paymentIntent } = await stripe.confirmPayment({
        elements,
        confirmParams: {
          return_url: `${window.location.origin}/orders`,
        },
        redirect: 'if_required',
      });

      if (error) {
        toast.error(error.message);
        setLoading(false);
        return;
      }

      if (paymentIntent.status === 'succeeded') {
        // Create order
        const orderResponse = await orderService.create(
          paymentIntent.id,
          shippingAddressId
        );

        if (orderResponse.success) {
          // Reload cart to reflect cleared items
          await loadCart();
          toast.success('Order placed successfully!');
          const orderId = orderResponse.data?.id;
          if (orderId) {
            navigate(`/order-confirmation?order_id=${orderId}`);
          } else {
            onSuccess();
          }
        } else {
          toast.error('Failed to create order');
        }
      }
    } catch (error) {
      console.error('Payment error:', error);
      toast.error(error.message || 'Payment failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <PaymentElement />
      <button
        type="submit"
        disabled={!stripe || loading}
        className="w-full btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {loading ? 'Processing...' : `Pay $${amount.toFixed(2)}`}
      </button>
    </form>
  );
}

