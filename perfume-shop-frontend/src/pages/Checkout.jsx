import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Elements } from '@stripe/react-stripe-js';
import { stripeService } from '../services/stripe';
import { useCart } from '../context/CartContext';
import { shippingService } from '../services/shipping';
import { orderService } from '../services/orders';
import CheckoutForm from '../components/CheckoutForm';
import { MapPin, Plus } from 'lucide-react';
import toast from 'react-hot-toast';

export default function Checkout() {
  const { cartItems, cartTotal, loadCart } = useCart();
  const navigate = useNavigate();
  const [stripePromise, setStripePromise] = useState(null);
  const [shippingAddresses, setShippingAddresses] = useState([]);
  const [selectedAddress, setSelectedAddress] = useState(null);
  const [showAddressForm, setShowAddressForm] = useState(false);
  const [newAddress, setNewAddress] = useState({
    full_name: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    country: 'US',
    phone: '',
  });
  const [loading, setLoading] = useState(false);
  const [clientSecret, setClientSecret] = useState(null);
  const [paymentIntentId, setPaymentIntentId] = useState(null);

  useEffect(() => {
    if (cartItems.length === 0) {
      navigate('/cart');
      return;
    }
    initializeStripe();
    loadShippingAddresses();
  }, []);

  useEffect(() => {
    if (selectedAddress && stripePromise && !clientSecret) {
      const createPaymentIntent = async () => {
        try {
          setLoading(true);
          const response = await stripeService.createPaymentIntent(selectedAddress);
          if (response.success) {
            setClientSecret(response.data.client_secret);
            setPaymentIntentId(response.data.payment_intent_id);
          } else {
            toast.error('Failed to initialize payment');
          }
        } catch (error) {
          console.error('Failed to create payment intent:', error);
          toast.error('Failed to initialize payment');
        } finally {
          setLoading(false);
        }
      };
      createPaymentIntent();
    }
  }, [selectedAddress, stripePromise, clientSecret]);

  // Note: Payment intent amount validation happens in CheckoutForm before payment confirmation
  // This prevents charging wrong amounts if cart changes after payment intent creation

  const initializeStripe = async () => {
    const stripe = await stripeService.getStripe();
    setStripePromise(stripe);
  };

  const loadShippingAddresses = async () => {
    try {
      const response = await shippingService.getAll();
      if (response.success) {
        setShippingAddresses(response.data || []);
        if (response.data?.length > 0) {
          setSelectedAddress(response.data[0].id);
        }
      }
    } catch (error) {
      console.error('Failed to load shipping addresses:', error);
    }
  };

  const handleAddAddress = async (e) => {
    e.preventDefault();
    try {
      const response = await shippingService.create(newAddress);
      if (response.success) {
        toast.success('Address added successfully');
        setShowAddressForm(false);
        setNewAddress({
          full_name: '',
          address: '',
          city: '',
          state: '',
          zip: '',
          country: 'US',
          phone: '',
        });
        await loadShippingAddresses();
        // Select the newly created address
        if (response.data?.id) {
          setSelectedAddress(response.data.id);
          setClientSecret(null); // Reset client secret to trigger new payment intent
          setPaymentIntentId(null);
        }
      }
    } catch (error) {
      toast.error('Failed to add address');
    }
  };

  const subtotal = cartTotal;
  const tax = subtotal * 0.1;
  const shipping = 10;
  const total = subtotal + tax + shipping;

  if (!stripePromise) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-3xl font-bold mb-8">Checkout</h1>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Checkout Form */}
        <div className="lg:col-span-2 space-y-6">
          {/* Shipping Address */}
          <div className="card p-6">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-xl font-semibold flex items-center space-x-2">
                <MapPin className="w-5 h-5" />
                <span>Shipping Address</span>
              </h2>
              <button
                onClick={() => setShowAddressForm(!showAddressForm)}
                className="btn-outline text-sm flex items-center space-x-1"
              >
                <Plus className="w-4 h-4" />
                <span>Add New</span>
              </button>
            </div>

            {/* Address Form */}
            {showAddressForm && (
              <form onSubmit={handleAddAddress} className="mb-4 p-4 bg-gray-50 rounded-lg">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <input
                    id="full_name"
                    name="full_name"
                    type="text"
                    autoComplete="name"
                    placeholder="Full Name"
                    value={newAddress.full_name}
                    onChange={(e) => setNewAddress({ ...newAddress, full_name: e.target.value })}
                    className="input-field"
                    required
                  />
                  <input
                    id="phone"
                    name="phone"
                    type="tel"
                    autoComplete="tel"
                    placeholder="Phone"
                    value={newAddress.phone}
                    onChange={(e) => setNewAddress({ ...newAddress, phone: e.target.value })}
                    className="input-field"
                    required
                  />
                  <input
                    id="address"
                    name="address"
                    type="text"
                    autoComplete="street-address"
                    placeholder="Address"
                    value={newAddress.address}
                    onChange={(e) => setNewAddress({ ...newAddress, address: e.target.value })}
                    className="input-field md:col-span-2"
                    required
                  />
                  <input
                    id="city"
                    name="city"
                    type="text"
                    autoComplete="address-level2"
                    placeholder="City"
                    value={newAddress.city}
                    onChange={(e) => setNewAddress({ ...newAddress, city: e.target.value })}
                    className="input-field"
                    required
                  />
                  <input
                    id="state"
                    name="state"
                    type="text"
                    autoComplete="address-level1"
                    placeholder="State"
                    value={newAddress.state}
                    onChange={(e) => setNewAddress({ ...newAddress, state: e.target.value })}
                    className="input-field"
                    required
                  />
                  <input
                    id="zip"
                    name="zip"
                    type="text"
                    autoComplete="postal-code"
                    placeholder="ZIP Code"
                    value={newAddress.zip}
                    onChange={(e) => setNewAddress({ ...newAddress, zip: e.target.value })}
                    className="input-field"
                    required
                  />
                  <select
                    id="country"
                    name="country"
                    autoComplete="country"
                    value={newAddress.country}
                    onChange={(e) => setNewAddress({ ...newAddress, country: e.target.value })}
                    className="input-field"
                  >
                    <option value="US">United States</option>
                    <option value="CA">Canada</option>
                    <option value="UK">United Kingdom</option>
                  </select>
                </div>
                <div className="flex space-x-2 mt-4">
                  <button type="submit" className="btn-primary">
                    Save Address
                  </button>
                  <button
                    type="button"
                    onClick={() => setShowAddressForm(false)}
                    className="btn-secondary"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            )}

            {/* Address List */}
            <div className="space-y-2">
              {shippingAddresses.map((address) => (
                <label
                  key={address.id}
                  className={`block p-4 border-2 rounded-lg cursor-pointer ${
                    selectedAddress === address.id
                      ? 'border-primary-600 bg-primary-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="address"
                    value={address.id}
                    checked={selectedAddress === address.id}
                    onChange={(e) => {
                      const newAddressId = parseInt(e.target.value);
                      setSelectedAddress(newAddressId);
                      setClientSecret(null); // Reset client secret when address changes
                      setPaymentIntentId(null);
                    }}
                    className="mr-3"
                  />
                  <div>
                    <p className="font-semibold">{address.full_name}</p>
                    <p className="text-sm text-gray-600">
                      {address.address}, {address.city}, {address.state} {address.zip}
                    </p>
                    <p className="text-sm text-gray-600">{address.phone}</p>
                  </div>
                </label>
              ))}
            </div>

            {shippingAddresses.length === 0 && !showAddressForm && (
              <p className="text-gray-600 text-center py-4">
                No shipping addresses. Please add one to continue.
              </p>
            )}
          </div>

          {/* Payment */}
          {selectedAddress && clientSecret && (
            <div className="card p-6">
              <h2 className="text-xl font-semibold mb-4">Payment</h2>
              <Elements stripe={stripePromise} options={{ clientSecret }}>
                <CheckoutForm
                  amount={total}
                  shippingAddressId={selectedAddress}
                  paymentIntentId={paymentIntentId}
                  onSuccess={() => {
                    loadCart();
                    navigate('/orders');
                  }}
                />
              </Elements>
            </div>
          )}
          {selectedAddress && !clientSecret && loading && (
            <div className="card p-6">
              <div className="flex items-center justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                <span className="ml-3">Initializing payment...</span>
              </div>
            </div>
          )}
        </div>

        {/* Order Summary */}
        <div className="lg:col-span-1">
          <div className="card p-6 sticky top-24">
            <h2 className="text-xl font-bold mb-4">Order Summary</h2>
            <div className="space-y-2 mb-4">
              {cartItems.map((item) => (
                <div key={item.id} className="flex justify-between text-sm">
                  <span>
                    {item.product?.name} x {item.quantity}
                  </span>
                  <span>
                    ${(parseFloat(item.product?.price || 0) * item.quantity).toFixed(2)}
                  </span>
                </div>
              ))}
            </div>
            <div className="border-t pt-2 space-y-2">
              <div className="flex justify-between">
                <span>Subtotal</span>
                <span>${subtotal.toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Tax</span>
                <span>${tax.toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Shipping</span>
                <span>${shipping.toFixed(2)}</span>
              </div>
              <div className="border-t pt-2 flex justify-between font-bold text-lg">
                <span>Total</span>
                <span>${total.toFixed(2)}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

