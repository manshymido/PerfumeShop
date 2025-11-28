import api from './api';

export const cartService = {
  getCart: async () => {
    const response = await api.get('/v1/cart');
    return response.data;
  },

  addToCart: async (productId, quantity = 1) => {
    const response = await api.post('/v1/cart', {
      product_id: productId,
      quantity,
    });
    return response.data;
  },

  updateCartItem: async (cartItemId, quantity) => {
    const response = await api.put(`/v1/cart/${cartItemId}`, { quantity });
    return response.data;
  },

  removeFromCart: async (cartItemId) => {
    const response = await api.delete(`/v1/cart/${cartItemId}`);
    return response.data;
  },

  clearCart: async () => {
    const response = await api.delete('/v1/cart');
    return response.data;
  },
};

