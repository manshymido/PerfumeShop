import api from './api';

export const authService = {
  register: async (data) => {
    const response = await api.post('/v1/register', data);
    if (response.data.success) {
      localStorage.setItem('auth_token', response.data.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.data.user));
    }
    return response.data;
  },

  login: async (email, password) => {
    const response = await api.post('/v1/login', { email, password });
    if (response.data.success) {
      localStorage.setItem('auth_token', response.data.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.data.user));
      
      // Merge guest cart if exists
      const sessionId = localStorage.getItem('session_id');
      if (sessionId) {
        try {
          const mergeResponse = await api.post('/v1/cart/merge', { session_id: sessionId });
          if (!mergeResponse.data.success) {
            console.warn('Cart merge warning:', mergeResponse.data.message);
          }
        } catch (error) {
          // Don't fail login if cart merge fails, just log it
          console.error('Failed to merge cart:', error.response?.data?.message || error.message);
        }
      }
    }
    return response.data;
  },

  logout: async () => {
    try {
      await api.post('/v1/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
    }
  },

  getUser: async () => {
    const response = await api.get('/v1/user');
    if (response.data.success) {
      localStorage.setItem('user', JSON.stringify(response.data.data));
    }
    return response.data;
  },

  updateProfile: async (data) => {
    const response = await api.put('/v1/user', data);
    if (response.data.success) {
      localStorage.setItem('user', JSON.stringify(response.data.data));
    }
    return response.data;
  },

  forgotPassword: async (email) => {
    const response = await api.post('/v1/forgot-password', { email });
    return response.data;
  },

  resetPassword: async (data) => {
    const response = await api.post('/v1/reset-password', data);
    return response.data;
  },
};

