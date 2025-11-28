import { useEffect } from 'react';
import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { LayoutDashboard, Package, ShoppingBag, Users, Box } from 'lucide-react';

export default function AdminLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (!user || user.role !== 'admin') {
      navigate('/');
    }
  }, [user]);

  if (!user || user.role !== 'admin') {
    return null;
  }

  return (
    <div className="min-h-screen flex">
      {/* Sidebar */}
      <aside className="w-64 bg-gray-900 text-white min-h-screen">
        <div className="p-6">
          <h2 className="text-xl font-bold mb-8">Admin Panel</h2>
          <nav className="space-y-2">
            <Link
              to="/admin/dashboard"
              className="flex items-center space-x-2 px-4 py-2 rounded hover:bg-gray-800"
            >
              <LayoutDashboard className="w-5 h-5" />
              <span>Dashboard</span>
            </Link>
            <Link
              to="/admin/products"
              className="flex items-center space-x-2 px-4 py-2 rounded hover:bg-gray-800"
            >
              <Package className="w-5 h-5" />
              <span>Products</span>
            </Link>
            <Link
              to="/admin/orders"
              className="flex items-center space-x-2 px-4 py-2 rounded hover:bg-gray-800"
            >
              <ShoppingBag className="w-5 h-5" />
              <span>Orders</span>
            </Link>
            <Link
              to="/admin/inventory"
              className="flex items-center space-x-2 px-4 py-2 rounded hover:bg-gray-800"
            >
              <Box className="w-5 h-5" />
              <span>Inventory</span>
            </Link>
            <Link
              to="/admin/users"
              className="flex items-center space-x-2 px-4 py-2 rounded hover:bg-gray-800"
            >
              <Users className="w-5 h-5" />
              <span>Users</span>
            </Link>
          </nav>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 bg-gray-50">
        <Outlet />
      </main>
    </div>
  );
}

