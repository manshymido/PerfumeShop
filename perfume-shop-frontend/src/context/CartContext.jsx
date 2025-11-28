import { createContext, useContext, useState, useEffect, useRef } from 'react';
import { cartService } from '../services/cart';
import { useAuth } from './AuthContext';
import toast from 'react-hot-toast';

const CartContext = createContext();

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const { isAuthenticated } = useAuth();
  const isLoadingRef = useRef(false);

  useEffect(() => {
    // Prevent duplicate calls in StrictMode
    if (isLoadingRef.current) return;

    if (isAuthenticated || localStorage.getItem('session_id')) {
      loadCart();
    }
  }, [isAuthenticated]);

  const loadCart = async () => {
    // Prevent concurrent calls
    if (isLoadingRef.current) return;
    
    try {
      isLoadingRef.current = true;
      setLoading(true);
      const response = await cartService.getCart();
      if (response.success) {
        setCartItems(response.data || []);
      }
    } catch (error) {
      console.error('Failed to load cart:', error);
    } finally {
      setLoading(false);
      isLoadingRef.current = false;
    }
  };

  const addToCart = async (productId, quantity = 1) => {
    try {
      const response = await cartService.addToCart(productId, quantity);
      if (response.success) {
        await loadCart();
        toast.success('Added to cart!');
        return { success: true };
      }
      return { success: false, message: response.message };
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to add to cart';
      toast.error(message);
      return { success: false, message };
    }
  };

  const updateQuantity = async (cartItemId, quantity) => {
    try {
      const response = await cartService.updateCartItem(cartItemId, quantity);
      if (response.success) {
        await loadCart();
        return { success: true };
      }
      return { success: false, message: response.message };
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to update cart';
      toast.error(message);
      return { success: false, message };
    }
  };

  const removeFromCart = async (cartItemId) => {
    try {
      const response = await cartService.removeFromCart(cartItemId);
      if (response.success) {
        await loadCart();
        toast.success('Removed from cart');
        return { success: true };
      }
      return { success: false, message: response.message };
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to remove from cart';
      toast.error(message);
      return { success: false, message };
    }
  };

  const clearCart = async () => {
    try {
      const response = await cartService.clearCart();
      if (response.success) {
        setCartItems([]);
        toast.success('Cart cleared');
        return { success: true };
      }
      return { success: false, message: response.message };
    } catch (error) {
      const message = error.response?.data?.message || 'Failed to clear cart';
      toast.error(message);
      return { success: false, message };
    }
  };

  const cartTotal = cartItems.reduce((total, item) => {
    return total + (item.product?.price || 0) * item.quantity;
  }, 0);

  const cartCount = cartItems.reduce((count, item) => count + item.quantity, 0);

  const value = {
    cartItems,
    loading,
    addToCart,
    updateQuantity,
    removeFromCart,
    clearCart,
    loadCart,
    cartTotal,
    cartCount,
  };

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};

