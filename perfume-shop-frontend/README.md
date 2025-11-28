# Perfume Shop Frontend

A modern React frontend for the Perfume Shop e-commerce platform.

## Features

- 🛍️ Product browsing and search
- 🛒 Shopping cart with guest support
- 💳 Stripe payment integration
- 👤 User authentication
- 📦 Order management
- ❤️ Wishlist functionality
- ⭐ Product reviews and ratings
- 📱 Responsive design

## Tech Stack

- **React 18** - UI library
- **Vite** - Build tool
- **React Router** - Routing
- **Tailwind CSS** - Styling
- **Axios** - HTTP client
- **Stripe** - Payment processing
- **React Hot Toast** - Notifications

## Setup

1. Install dependencies:
```bash
npm install
```

2. Create a `.env` file in the root directory:
```env
VITE_API_URL=http://localhost:8000/api
VITE_STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_key_here
```

3. Start the development server:
```bash
npm run dev
```

The app will be available at `http://localhost:5173`

## Build

To build for production:
```bash
npm run build
```

The built files will be in the `dist` directory.

## Environment Variables

- `VITE_API_URL` - Backend API URL (default: http://localhost:8000/api)
- `VITE_STRIPE_PUBLISHABLE_KEY` - Stripe publishable key

## Project Structure

```
src/
├── components/     # Reusable components
├── context/        # React contexts (Auth, Cart)
├── pages/          # Page components
├── services/       # API service functions
└── App.jsx         # Main app component
```

