import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Request deduplication cache (stores pending promises)
const pendingRequests = new Map();

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

// Generate or retrieve session ID
const getSessionId = () => {
  let sessionId = localStorage.getItem('session_id');
  if (!sessionId) {
    sessionId = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    localStorage.setItem('session_id', sessionId);
  }
  return sessionId;
};

// Request interceptor to add auth token and session ID
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    const sessionId = getSessionId();
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    config.headers['X-Session-ID'] = sessionId;
    
    // Don't set Content-Type for FormData - let browser set it with boundary
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type'];
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Wrapper function to deduplicate GET requests
const originalGet = api.get.bind(api);
api.get = function(url, config = {}) {
  const requestKey = `get:${url}`;
  
  // If there's already a pending request for this URL, return it
  if (pendingRequests.has(requestKey)) {
    return pendingRequests.get(requestKey);
  }
  
  // Create new request and cache it
  const requestPromise = originalGet(url, config);
  pendingRequests.set(requestKey, requestPromise);
  
  // Remove from cache after request completes (success or error)
  requestPromise.finally(() => {
    setTimeout(() => {
      pendingRequests.delete(requestKey);
    }, 100); // Small delay to catch rapid duplicate calls
  });
  
  return requestPromise;
};

// Response interceptor to handle errors and cleanup deduplication cache
api.interceptors.response.use(
  (response) => {
    // Clear pending request from deduplication cache
    if (response.config) {
      const requestKey = `${response.config.method}:${response.config.url}`;
      pendingRequests.delete(requestKey);
    }
    return response;
  },
  (error) => {
    // Clear pending request from deduplication cache on error
    if (error.config) {
      const requestKey = `${error.config.method}:${error.config.url}`;
      pendingRequests.delete(requestKey);
    }
    
    if (error.response?.status === 401) {
      // Unauthorized - clear token and redirect to login
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;

