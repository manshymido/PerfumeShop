# PerfumeShop API

A comprehensive RESTful API for an e-commerce perfume shop built with Laravel 12. This API provides complete functionality for managing products, orders, payments, inventory, and user management.

## Features

- 🔐 **Authentication & Authorization**
  - User registration and login
  - Password reset functionality
  - Role-based access control (Admin, User)
  - Sanctum token-based authentication

- 🛍️ **Product Management**
  - Browse products with filtering and pagination
  - Product categories
  - Product images
  - Recently viewed products
  - Recommended products

- 🛒 **Shopping Cart**
  - Guest cart support
  - Authenticated user cart
  - Cart merging on login
  - Real-time cart calculations

- 💳 **Payment Processing**
  - Stripe payment integration
  - Payment intent creation and management
  - Webhook handling for payment events
  - Order validation before checkout

- 📦 **Order Management**
  - Order creation and tracking
  - Order status history
  - Order cancellation
  - Invoice generation
  - Order refunds (Admin)

- ⭐ **Reviews & Ratings**
  - Product reviews
  - Review management (create, update, delete)
  - Review policies for ownership

- ❤️ **Wishlist**
  - Add/remove products from wishlist
  - Move items from wishlist to cart

- 📍 **Shipping Addresses**
  - Multiple shipping addresses per user
  - Address management (CRUD operations)

- 👨‍💼 **Admin Panel**
  - Dashboard with statistics
  - Product management (CRUD)
  - Order management and status updates
  - Inventory management
  - Low stock alerts
  - User management
  - Role management

- 📊 **Inventory Management**
  - Stock tracking
  - Low stock alerts via email
  - Inventory updates

## Technology Stack

- **Framework**: Laravel 12
- **PHP**: ^8.2
- **Authentication**: Laravel Sanctum
- **Payment**: Stripe PHP SDK
- **API Documentation**: L5-Swagger (OpenAPI/Swagger)
- **Database**: SQLite (default), supports MySQL/PostgreSQL

## Requirements

- PHP >= 8.2
- Composer
- Node.js & NPM (for frontend assets)
- Stripe account (for payment processing)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/manshymido/PerfumeShop.git
   cd PerfumeShop/perfume-shop-api
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure environment variables**
   Edit `.env` file and set:
   - Database configuration
   - Stripe keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`)
   - Mail configuration
   - App URL

5. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Generate API documentation**
   ```bash
   php artisan l5-swagger:generate
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

   Or use the dev script:
   ```bash
   composer run dev
   ```

## API Documentation

Once the application is running, you can access the Swagger API documentation at:
```
http://localhost:8000/api/documentation
```

## API Endpoints

### Authentication
- `POST /api/v1/register` - Register a new user
- `POST /api/v1/login` - Login user
- `POST /api/v1/logout` - Logout user (authenticated)
- `POST /api/v1/forgot-password` - Request password reset
- `POST /api/v1/reset-password` - Reset password
- `GET /api/v1/user` - Get authenticated user
- `PUT /api/v1/user` - Update user profile

### Products
- `GET /api/v1/products` - List all products
- `GET /api/v1/products/{id}` - Get product details
- `GET /api/v1/products/recommended` - Get recommended products
- `GET /api/v1/products/category/{categoryId}` - Get products by category
- `GET /api/v1/products/recently-viewed` - Get recently viewed products (authenticated)

### Categories
- `GET /api/v1/categories` - List all categories
- `GET /api/v1/categories/{id}` - Get category details

### Cart
- `GET /api/v1/cart` - Get cart items
- `POST /api/v1/cart` - Add item to cart
- `PUT /api/v1/cart/{id}` - Update cart item
- `DELETE /api/v1/cart/{id}` - Remove cart item
- `DELETE /api/v1/cart` - Clear cart
- `POST /api/v1/cart/merge` - Merge guest cart with user cart (authenticated)

### Orders
- `GET /api/v1/orders` - Get user orders (authenticated)
- `POST /api/v1/orders` - Create new order (authenticated)
- `GET /api/v1/orders/{id}` - Get order details (authenticated)
- `PUT /api/v1/orders/{id}/cancel` - Cancel order (authenticated)
- `GET /api/v1/orders/{id}/invoice` - Get order invoice (authenticated)

### Checkout
- `POST /api/v1/checkout/validate` - Validate checkout data (authenticated)
- `POST /api/v1/checkout/create-intent` - Create payment intent (authenticated)
- `POST /api/v1/checkout/update-intent` - Update payment intent (authenticated)

### Reviews
- `GET /api/v1/products/{id}/reviews` - Get product reviews
- `POST /api/v1/products/{id}/reviews` - Create review (authenticated)
- `PUT /api/v1/reviews/{id}` - Update review (authenticated)
- `DELETE /api/v1/reviews/{id}` - Delete review (authenticated)

### Wishlist
- `GET /api/v1/wishlist` - Get wishlist items (authenticated)
- `POST /api/v1/wishlist` - Add to wishlist (authenticated)
- `DELETE /api/v1/wishlist/{id}` - Remove from wishlist (authenticated)
- `POST /api/v1/wishlist/{id}/move-to-cart` - Move item to cart (authenticated)

### Shipping Addresses
- `GET /api/v1/shipping-addresses` - List shipping addresses (authenticated)
- `POST /api/v1/shipping-addresses` - Create shipping address (authenticated)
- `GET /api/v1/shipping-addresses/{id}` - Get shipping address (authenticated)
- `PUT /api/v1/shipping-addresses/{id}` - Update shipping address (authenticated)
- `DELETE /api/v1/shipping-addresses/{id}` - Delete shipping address (authenticated)

### Admin Endpoints
All admin endpoints require authentication and admin role.

- `GET /api/v1/admin/dashboard/stats` - Dashboard statistics
- `GET /api/v1/admin/products` - List all products
- `POST /api/v1/admin/products` - Create product
- `PUT /api/v1/admin/products/{id}` - Update product
- `DELETE /api/v1/admin/products/{id}` - Delete product
- `GET /api/v1/admin/orders` - List all orders
- `GET /api/v1/admin/orders/{id}` - Get order details
- `PUT /api/v1/admin/orders/{id}/status` - Update order status
- `POST /api/v1/admin/orders/{id}/refund` - Refund order
- `GET /api/v1/admin/inventory` - List inventory
- `GET /api/v1/admin/inventory/low-stock` - Get low stock products
- `PUT /api/v1/admin/inventory/{id}` - Update inventory
- `GET /api/v1/admin/users` - List all users
- `PUT /api/v1/admin/users/{id}/role` - Update user role
- `DELETE /api/v1/admin/users/{id}` - Deactivate user

### Webhooks
- `POST /api/v1/webhooks/stripe` - Stripe webhook handler

## Rate Limiting

- Public API endpoints: 60 requests per minute
- Admin endpoints: 30 requests per minute

## Testing

Run the test suite:
```bash
composer test
```

Or use PHPUnit directly:
```bash
php artisan test
```

## Queue Jobs

The application uses Laravel queues for:
- Sending welcome emails
- Sending order confirmation emails
- Sending order status update emails
- Sending low stock alert emails

Start the queue worker:
```bash
php artisan queue:work
```

## Security Features

- CSRF protection
- Rate limiting
- Input validation
- Authorization policies
- Secure password hashing
- Token-based authentication
- Webhook signature verification (Stripe)

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, email support@perfumeshop.com or open an issue in the repository.
