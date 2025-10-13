<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Admin Dashboard - EVC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
    <style>
        /* Custom styles for fullscreen map */
        .map-container {
            height: calc(100vh - 80px);
        }

        .sidebar-collapsed {
            width: 70px !important;
        }

        .sidebar-collapsed .sidebar-text {
            display: none;
        }

        .sidebar-collapsed .sidebar-logo-text {
            display: none;
        }

        /* Normalize cursor behavior */
        * {
            cursor: default;
        }

        /* Only allow pointer cursor on specific interactive elements */
        button,
        a,
        input,
        select,
        textarea,
        .cursor-pointer,
        [onclick],
        [role="button"] {
            cursor: pointer !important;
        }

        /* Text cursor for text inputs */
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"],
        textarea {
            cursor: text !important;
        }

        /* Disable hover scale animations to prevent cursor confusion */
        .no-scale-hover:hover {
            transform: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Hover effects */
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Animation classes */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        /* Gradient backgrounds */
        .gradient-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .gradient-green {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
        }

        .gradient-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #5b21b6 100%);
        }

        .gradient-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .gradient-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        /* Button cursor */
        button, .cursor-pointer {
            cursor: pointer !important;
        }

        /* Input cursor */
        input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea {
            cursor: text !important;
        }
    </style>
</head>
<body class="bg-slate-900 text-white overflow-hidden" x-data="{
    sidebarOpen: true,
    activeSection: 'dashboard',
    showAddStationModal: false,
    newStation: { name: '', address: '', lat: '', lng: '', slots: 4, price: 25 },
    users: @json($users ?? []),
    allReservations: [
        {id: 1, user: 'Sarah Wilson', station: 'Downtown Station', date: '2025-10-01', time: '10:00 AM', duration: '2 hours', status: 'confirmed', amount: '$50.00'},
        {id: 2, user: 'David Brown', station: 'Mall Station', date: '2025-10-02', time: '2:00 PM', duration: '1.5 hours', status: 'pending', amount: '$37.50'},
        {id: 3, user: 'Lisa Davis', station: 'Airport Station', date: '2025-10-03', time: '8:00 AM', duration: '3 hours', status: 'confirmed', amount: '$75.00'},
        {id: 4, user: 'John Doe', station: 'City Center', date: '2025-10-04', time: '11:00 AM', duration: '1 hour', status: 'completed', amount: '$25.00'}
    ],
    stations: [
        {id: 1, name: 'Downtown Station', address: '123 Main St', lat: 40.7128, lng: -74.0060, slots: 4, available: 2, price: 25, status: 'active'},
        {id: 2, name: 'Mall Station', address: '456 Oak Ave', lat: 40.7589, lng: -73.9851, slots: 6, available: 4, price: 20, status: 'active'},
        {id: 3, name: 'Airport Station', address: '789 Pine Rd', lat: 40.6892, lng: -74.1445, slots: 8, available: 0, price: 30, status: 'maintenance'}
    ]
}">
    <!-- Header -->
    <header class="bg-slate-800 shadow-lg border-b border-slate-700 relative z-50">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-3">
                <!-- Sidebar Toggle -->
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-slate-700 transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- EVC Logo -->
                <button @click="window.location.href='/'" class="flex items-center space-x-3 hover:opacity-80 transition-opacity">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center relative">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11 1l-8 9h5v12h6V10h5L11 1z"/>
                        </svg>
                    </div>
                    <div class="text-green-500 font-bold text-2xl tracking-wider sidebar-logo-text" x-show="sidebarOpen" x-transition>EVC</div>
                    <div class="bg-red-500 text-white px-2 py-1 rounded text-xs font-semibold sidebar-logo-text" x-show="sidebarOpen" x-transition>ADMIN</div>
                </button>
            </div>

            <div class="flex items-center space-x-4">
                <!-- Search Bar -->
                <div class="relative">
                    <input type="text" id="search-input" placeholder="Search users, stations..."
                           class="bg-slate-700 border border-slate-600 rounded-lg px-4 py-2 pl-10 text-white placeholder-gray-400 w-64 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>
                    </svg>
                </div>

                <!-- Stats Summary -->
                <div class="hidden md:flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-slate-700 px-3 py-2 rounded-lg">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span x-text="users ? users.length : 0">0</span>
                        <span class="text-gray-400">Users</span>
                    </div>
                    <div class="flex items-center space-x-2 bg-slate-700 px-3 py-2 rounded-lg">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <span x-text="stations.length">0</span>
                        <span class="text-gray-400">Stations</span>
                    </div>
                </div>

                <!-- Admin Profile -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2 bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm transition-colors">
                        <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">A</span>
                        </div>
                        <div class="text-left">
                            <div class="font-medium">Admin</div>
                            <div class="text-xs text-gray-400">admin@evc.com</div>
                        </div>
                    </div>

                    <!-- Logout -->
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded-lg text-sm transition-colors" title="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="flex h-full">
        <!-- Sidebar -->
        <div class="bg-slate-800 border-r border-slate-700 transition-all duration-300 relative z-40"
             :class="sidebarOpen ? 'w-64' : 'w-16'">
            <div class="p-4">
                <ul class="space-y-2">
                    <!-- Dashboard -->
                    <li>
                        <button @click="activeSection = 'dashboard'"
                                :class="activeSection === 'dashboard' ? 'bg-green-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-slate-700'"
                                class="flex items-center p-3 rounded-lg transition-all duration-300 w-full text-left">
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                            </svg>
                            <span x-show="sidebarOpen" x-transition class="sidebar-text font-medium">Dashboard</span>
                        </button>
                    </li>

                    <!-- Users Management -->
                    <li>
                        <button @click="activeSection = 'users'"
                                :class="activeSection === 'users' ? 'bg-blue-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-slate-700'"
                                class="flex items-center p-3 rounded-lg transition-all duration-300 w-full text-left">
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            <span x-show="sidebarOpen" x-transition class="sidebar-text font-medium">Users</span>
                        </button>
                    </li>

                    <!-- Reservations -->
                    <li>
                        <button @click="activeSection = 'reservations'"
                                :class="activeSection === 'reservations' ? 'bg-purple-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-slate-700'"
                                class="flex items-center p-3 rounded-lg transition-all duration-300 w-full text-left">
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                            </svg>
                            <span x-show="sidebarOpen" x-transition class="sidebar-text font-medium">Reservations</span>
                        </button>
                    </li>

                    <!-- Station Management -->
                    <li>
                        <button @click="activeSection = 'stations'"
                                :class="activeSection === 'stations' ? 'bg-yellow-500 text-white shadow-lg' : 'text-gray-300 hover:text-white hover:bg-slate-700'"
                                class="flex items-center p-3 rounded-lg transition-all duration-300 w-full text-left">
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            <span x-show="sidebarOpen" x-transition class="sidebar-text font-medium">Stations</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 relative">
            <!-- Dashboard Overview (Default) -->
            <div x-show="activeSection === 'dashboard'" class="w-full h-full flex">
                <!-- Dashboard Content -->
                <div class="flex-1 bg-slate-700 p-6 overflow-y-auto">
                    <div class="max-w-7xl mx-auto">
                        <div class="mb-8">
                            <h1 class="text-3xl font-bold text-white mb-2">Admin Dashboard</h1>
                            <p class="text-gray-400">Manage your EV charging network</p>
                        </div>

                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 rounded-xl text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-green-100 text-sm">Total Users</p>
                                        <p class="text-3xl font-bold" x-text="users ? users.length : '{{ $totalUsers ?? 0 }}'">0</p>
                                    </div>
                                    <div class="bg-green-400 bg-opacity-30 p-3 rounded-full">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 rounded-xl text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-blue-100 text-sm">Active Bookings</p>
                                        <p class="text-3xl font-bold">{{ $activeBookings ?? 0 }}</p>
                                    </div>
                                    <div class="bg-blue-400 bg-opacity-30 p-3 rounded-full">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6 rounded-xl text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-purple-100 text-sm">Total Stations</p>
                                        <p class="text-3xl font-bold" x-text="stations.length">{{ $totalStations ?? 0 }}</p>
                                    </div>
                                    <div class="bg-purple-400 bg-opacity-30 p-3 rounded-full">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-6 rounded-xl text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-yellow-100 text-sm">Monthly Revenue</p>
                                        <p class="text-3xl font-bold">${{ $monthlyRevenue ?? 0 }}</p>
                                    </div>
                                    <div class="bg-yellow-400 bg-opacity-30 p-3 rounded-full">
                                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-slate-800 rounded-xl p-6 mb-8">
                            <h2 class="text-xl font-bold text-white mb-4">Recent Users</h2>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="border-b border-slate-700">
                                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Name</th>
                                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Email</th>
                                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Joined</th>
                                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Bookings</th>
                                            <th class="text-left py-3 px-4 text-gray-300 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($users) && $users->count() > 0)
                                            @foreach($users as $user)
                                            <tr class="border-b border-slate-700 hover:bg-slate-700">
                                                <td class="py-3 px-4 text-white">{{ $user->name }}</td>
                                                <td class="py-3 px-4 text-gray-300">{{ $user->email }}</td>
                                                <td class="py-3 px-4 text-gray-300">{{ $user->created_at->format('M d, Y') }}</td>
                                                <td class="py-3 px-4 text-gray-300">{{ $user->bookings_count ?? 0 }}</td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="5" class="py-8 text-center text-gray-400">No users found</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Section -->
            <div x-show="activeSection === 'users'" class="w-full h-full flex">
                <div class="flex-1 bg-slate-700 p-6 overflow-y-auto">
                    <div class="max-w-7xl mx-auto">
                        <div class="mb-8">
                            <h1 class="text-3xl font-bold text-white mb-2">Users Management</h1>
                            <p class="text-gray-400">View and manage all registered users</p>
                        </div>

                        <!-- Users List -->
                        <div class="bg-slate-800 rounded-xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-slate-900 border-b border-slate-700">
                                            <th class="text-left py-4 px-6 text-gray-300 font-medium">User</th>
                                            <th class="text-left py-4 px-6 text-gray-300 font-medium">Contact</th>
                                            <th class="text-left py-4 px-6 text-gray-300 font-medium">Joined</th>
                                            <th class="text-left py-4 px-6 text-gray-300 font-medium">Bookings</th>
                                            <th class="text-left py-4 px-6 text-gray-300 font-medium">Status</th>
                                            <th class="text-left py-4 px-6 text-gray-300 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($users) && $users->count() > 0)
                                            @foreach($users as $user)
                                            <tr class="border-b border-slate-700 hover:bg-slate-700">
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center">
                                                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                                            <span class="text-white font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                        </div>
                                                        <div>
                                                            <div class="text-white font-medium">{{ $user->name }}</div>
                                                            <div class="text-gray-400 text-sm">ID: {{ $user->id }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <div class="text-white">{{ $user->email }}</div>
                                                    <div class="text-gray-400 text-sm">{{ $user->phone ?? 'N/A' }}</div>
                                                </td>
                                                <td class="py-4 px-6 text-gray-300">{{ $user->created_at->format('M d, Y') }}</td>
                                                <td class="py-4 px-6 text-white font-semibold">{{ $user->bookings_count ?? 0 }}</td>
                                                <td class="py-4 px-6">
                                                    <span class="px-3 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <div class="flex space-x-2">
                                                        <button class="bg-blue-500 hover:bg-blue-600 px-3 py-1 rounded text-xs text-white transition-colors">View</button>
                                                        <button class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded text-xs text-white transition-colors">Edit</button>
                                                        <button class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs text-white transition-colors">Suspend</button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6" class="py-8 text-center text-gray-400">No users found</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservations Section -->
            <div x-show="activeSection === 'reservations'" class="w-full h-full flex">
                <div class="flex-1 bg-slate-700 p-6 overflow-y-auto">
                    <div class="max-w-7xl mx-auto">
                        <div class="mb-8">
                            <h1 class="text-3xl font-bold text-white mb-2">Reservations Management</h1>
                            <p class="text-gray-400">View and manage all user reservations</p>
                        </div>

                        <!-- Reservations List -->
                        <div class="space-y-4">
                            <template x-for="reservation in allReservations" :key="reservation.id">
                                <div class="bg-slate-800 rounded-xl p-6 hover:bg-slate-750 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                                 :class="{
                                                     'bg-green-500': reservation.status === 'confirmed',
                                                     'bg-yellow-500': reservation.status === 'pending',
                                                     'bg-blue-500': reservation.status === 'completed',
                                                     'bg-red-500': reservation.status === 'cancelled'
                                                 }">
                                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-white font-semibold" x-text="`Reservation #${reservation.id}`"></h3>
                                                <p class="text-gray-300" x-text="`User: ${reservation.user}`"></p>
                                                <p class="text-gray-400 text-sm" x-text="`${reservation.station} • ${reservation.date} at ${reservation.time}`"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <div class="text-right">
                                                <div class="text-white font-semibold" x-text="reservation.amount"></div>
                                                <div class="text-gray-400 text-sm" x-text="reservation.duration"></div>
                                            </div>
                                            <span class="px-3 py-1 rounded-full text-xs"
                                                  :class="{
                                                      'bg-green-100 text-green-800': reservation.status === 'confirmed',
                                                      'bg-yellow-100 text-yellow-800': reservation.status === 'pending',
                                                      'bg-blue-100 text-blue-800': reservation.status === 'completed',
                                                      'bg-red-100 text-red-800': reservation.status === 'cancelled'
                                                  }"
                                                  x-text="reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)">
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stations Section -->
            <div x-show="activeSection === 'stations'" class="w-full h-full flex">
                <!-- Station List -->
                <div class="w-1/3 bg-slate-700 border-r border-slate-600 p-6 overflow-y-auto">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-white mb-2">Charging Stations</h2>
                        <button @click="showAddStationModal = true" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            + Add New Station
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="station in stations" :key="station.id">
                            <div class="bg-slate-800 rounded-lg p-4 hover:bg-slate-750 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-white font-semibold" x-text="station.name"></h3>
                                    <span class="px-2 py-1 rounded-full text-xs"
                                          :class="{
                                              'bg-green-100 text-green-800': station.status === 'active',
                                              'bg-yellow-100 text-yellow-800': station.status === 'maintenance',
                                              'bg-red-100 text-red-800': station.status === 'offline'
                                          }"
                                          x-text="station.status">
                                    </span>
                                </div>
                                <p class="text-gray-400 text-sm mb-2" x-text="station.address"></p>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-300" x-text="`${station.available}/${station.slots} available`"></span>
                                    <span class="text-green-400 font-medium" x-text="`$${station.price}/hr`"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Google Maps -->
                <div class="flex-1 relative">
                    <div id="stationsMap" class="w-full h-full bg-gray-300"></div>
                </div>
            </div>
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-2xl font-bold text-white mb-6">Admin Dashboard</h2>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-slate-800 rounded-lg p-6 border border-slate-600">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-gray-300 text-sm font-medium">Total Users</h3>
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="text-2xl font-bold text-white">{{ $totalUsers ?? 1247 }}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="text-green-400">+12% from last month</span>
                            </div>
                        </div>

                        <div class="bg-slate-800 rounded-lg p-6 border border-slate-600">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-gray-300 text-sm font-medium">Pending Approvals</h3>
                                <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="text-2xl font-bold text-white" x-text="pendingUsers.filter(u => u.otp_verified).length"></span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="text-yellow-400">Awaiting approval</span>
                            </div>
                        </div>

                        <div class="bg-slate-800 rounded-lg p-6 border border-slate-600">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-gray-300 text-sm font-medium">Active Stations</h3>
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 10V3L4 14h4v7l9-11h-4z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="text-2xl font-bold text-white">{{ $totalStations ?? 89 }}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="text-green-400">85% operational</span>
                            </div>
                        </div>

                        <div class="bg-slate-800 rounded-lg p-6 border border-slate-600">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-gray-300 text-sm font-medium">Monthly Revenue</h3>
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.51-1.31c-.562-.649-1.413-1.076-2.353-1.253V5z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="text-2xl font-bold text-white">${{ number_format($monthlyRevenue ?? 24567, 0) }}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="text-green-400">+8% from last month</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <button @click="activeSection = 'pending-users'" class="bg-slate-800 border border-slate-600 rounded-lg p-6 hover:bg-slate-700 transition-colors text-left">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Review Pending Users</h3>
                                    <p class="text-gray-400">Approve new user registrations</p>
                                </div>
                            </div>
                        </button>

                        <button @click="activeSection = 'users'" class="bg-slate-800 border border-slate-600 rounded-lg p-6 hover:bg-slate-700 transition-colors text-left">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Manage Users</h3>
                                    <p class="text-gray-400">View and manage all users</p>
                                </div>
                            </div>
                        </button>

                        <button @click="activeSection = 'stations'" class="bg-slate-800 border border-slate-600 rounded-lg p-6 hover:bg-slate-700 transition-colors text-left">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 10V3L4 14h4v7l9-11h-4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Station Management</h3>
                                    <p class="text-gray-400">Monitor charging stations</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pending User Approvals Section -->
            <div x-show="activeSection === 'pending-users'" class="w-full h-full bg-slate-700 p-6 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-white">Pending User Approvals</h2>
                        <div class="bg-yellow-500 text-black px-3 py-1 rounded-full text-sm font-semibold" x-text="`${pendingUsers.filter(u => u.otp_verified).length} pending`"></div>
                    </div>

                    <!-- Pending Users List -->
                    <div class="space-y-4">
                        <template x-for="user in pendingUsers" :key="user.id">
                            <div class="bg-slate-800 rounded-lg p-6 border border-slate-600">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-lg" x-text="user.name.charAt(0)"></span>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-white" x-text="user.name"></h3>
                                            <p class="text-gray-400 text-sm" x-text="user.email"></p>
                                            <p class="text-gray-400 text-sm" x-text="`Phone: ${user.phone}`"></p>
                                            <p class="text-gray-400 text-sm" x-text="`Signed up: ${user.signupDate}`"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <span class="inline-block px-2 py-1 rounded-full text-xs"
                                                  :class="user.otp_verified ? 'bg-green-600 text-white' : 'bg-yellow-600 text-white'"
                                                  x-text="user.otp_verified ? 'OTP Verified' : 'OTP Pending'">
                                            </span>
                                        </div>
                                        <div class="flex space-x-2" x-show="user.otp_verified">
                                            <button @click="approveUser(user)" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded text-sm text-white transition-colors">
                                                ✓ Approve
                                            </button>
                                            <button @click="rejectUser(user)" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm text-white transition-colors">
                                                ✗ Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- All Users Section -->
            <div x-show="activeSection === 'users'" class="w-full h-full bg-slate-700 p-6 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-2xl font-bold text-white mb-6">All Users</h2>

                    <!-- Users List -->
                    <div class="space-y-4">
                        <template x-for="user in allUsers" :key="user.id">
                            <div class="bg-slate-800 rounded-lg p-6 border border-slate-600">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-lg" x-text="user.name.charAt(0)"></span>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-white" x-text="user.name"></h3>
                                            <p class="text-gray-400 text-sm" x-text="user.email"></p>
                                            <p class="text-gray-400 text-sm" x-text="`Joined: ${user.joinDate}`"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-white" x-text="`${user.bookings} bookings`"></div>
                                            <span class="inline-block px-2 py-1 rounded-full text-xs"
                                                  :class="user.status === 'active' ? 'bg-green-600 text-white' : 'bg-gray-600 text-white'"
                                                  x-text="user.status.charAt(0).toUpperCase() + user.status.slice(1)">
                                            </span>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button class="bg-blue-500 hover:bg-blue-600 px-3 py-1 rounded text-sm text-white transition-colors">
                                                View Details
                                            </button>
                                            <button x-show="user.status === 'active'" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm text-white transition-colors">
                                                Suspend
                                            </button>
                                            <button x-show="user.status === 'inactive'" class="bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-sm text-white transition-colors">
                                                Activate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Reservations Section -->
            <div x-show="activeSection === 'reservations'" class="w-full h-full bg-slate-700 p-6 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-white">Reservations Management</h2>
                        <div class="flex space-x-3">
                            <div class="bg-teal-500 text-white px-3 py-1 rounded-full text-sm font-semibold" x-text="`${reservations.length} total`"></div>
                            <div class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold" x-text="`${reservations.filter(r => r.status === 'confirmed').length} confirmed`"></div>
                            <div class="bg-yellow-500 text-black px-3 py-1 rounded-full text-sm font-semibold" x-text="`${reservations.filter(r => r.status === 'pending').length} pending`"></div>
                        </div>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="flex space-x-2 mb-6" x-data="{ reservationFilter: 'all' }">
                        <button @click="reservationFilter = 'all'"
                                :class="reservationFilter === 'all' ? 'bg-teal-500 text-white' : 'bg-slate-600 text-gray-300 hover:bg-slate-500'"
                                class="px-4 py-2 rounded-lg transition-colors">
                            All Reservations
                        </button>
                        <button @click="reservationFilter = 'pending'"
                                :class="reservationFilter === 'pending' ? 'bg-yellow-500 text-black' : 'bg-slate-600 text-gray-300 hover:bg-slate-500'"
                                class="px-4 py-2 rounded-lg transition-colors">
                            Pending
                        </button>
                        <button @click="reservationFilter = 'confirmed'"
                                :class="reservationFilter === 'confirmed' ? 'bg-green-500 text-white' : 'bg-slate-600 text-gray-300 hover:bg-slate-500'"
                                class="px-4 py-2 rounded-lg transition-colors">
                            Confirmed
                        </button>
                        <button @click="reservationFilter = 'completed'"
                                :class="reservationFilter === 'completed' ? 'bg-blue-500 text-white' : 'bg-slate-600 text-gray-300 hover:bg-slate-500'"
                                class="px-4 py-2 rounded-lg transition-colors">
                            Completed
                        </button>
                    </div>

                    <!-- Reservations List -->
                    <div class="space-y-4">
                        <template x-for="reservation in reservations.filter(r => $data.reservationFilter === 'all' || r.status === $data.reservationFilter)" :key="reservation.id">
                            <div class="bg-slate-800 rounded-lg p-6 border border-slate-600 hover:border-slate-500 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                             :class="{
                                                 'bg-green-500': reservation.status === 'confirmed',
                                                 'bg-yellow-500': reservation.status === 'pending',
                                                 'bg-blue-500': reservation.status === 'completed',
                                                 'bg-red-500': reservation.status === 'cancelled'
                                             }">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-white" x-text="`#${reservation.id} - ${reservation.user}`"></h3>
                                            <p class="text-gray-400 text-sm" x-text="`Station: ${reservation.station}`"></p>
                                            <p class="text-gray-400 text-sm" x-text="`${reservation.date} at ${reservation.time}`"></p>
                                            <p class="text-gray-400 text-sm" x-text="`Duration: ${reservation.duration}`"></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-white" x-text="reservation.amount"></div>
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-green-100 text-green-800': reservation.status === 'confirmed',
                                                      'bg-yellow-100 text-yellow-800': reservation.status === 'pending',
                                                      'bg-blue-100 text-blue-800': reservation.status === 'completed',
                                                      'bg-red-100 text-red-800': reservation.status === 'cancelled'
                                                  }"
                                                  x-text="reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)">
                                            </span>
                                        </div>
                                        <div class="flex space-x-2">
                                            <template x-if="reservation.status === 'pending'">
                                                <div class="flex space-x-2">
                                                    <button @click="confirmReservation(reservation)" class="bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-sm text-white transition-colors">
                                                        ✓ Confirm
                                                    </button>
                                                    <button @click="cancelReservation(reservation)" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm text-white transition-colors">
                                                        ✗ Cancel
                                                    </button>
                                                </div>
                                            </template>
                                            <button class="bg-blue-500 hover:bg-blue-600 px-3 py-1 rounded text-sm text-white transition-colors">
                                                View Details
                                            </button>
                                            <template x-if="reservation.status === 'confirmed'">
                                                <button @click="completeReservation(reservation)" class="bg-purple-500 hover:bg-purple-600 px-3 py-1 rounded text-sm text-white transition-colors">
                                                    Mark Complete
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Stations Section -->
            <div x-show="activeSection === 'stations'" class="w-full h-full bg-slate-700 p-6 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-white">Charging Stations</h2>
                        <button @click="showAddStationModal = true" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>Add New Station</span>
                        </button>
                    </div>

                    <!-- Stations Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <template x-for="station in stations" :key="station.id">
                            <div class="bg-slate-800 rounded-lg p-6 border border-slate-600 hover:border-slate-500 transition-colors">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-white" x-text="station.name"></h3>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-800': station.status === 'active',
                                              'bg-yellow-100 text-yellow-800': station.status === 'maintenance',
                                              'bg-red-100 text-red-800': station.status === 'offline'
                                          }"
                                          x-text="station.status.charAt(0).toUpperCase() + station.status.slice(1)">
                                    </span>
                                </div>

                                <div class="space-y-2 text-sm text-gray-400 mb-4">
                                    <p x-text="station.address"></p>
                                    <p x-text="`${station.available}/${station.slots} slots available`"></p>
                                    <p x-text="`$${station.price}/hour`"></p>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex space-x-2">
                                        <button class="bg-blue-500 hover:bg-blue-600 px-3 py-1 rounded text-xs text-white transition-colors">
                                            View Map
                                        </button>
                                        <button class="bg-purple-500 hover:bg-purple-600 px-3 py-1 rounded text-xs text-white transition-colors">
                                            Edit
                                        </button>
                                    </div>
                                    <button x-show="station.status === 'active'" class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded text-xs text-black transition-colors">
                                        Maintenance
                                    </button>
                                    <button x-show="station.status === 'maintenance'" class="bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-xs text-white transition-colors">
                                        Activate
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Google Maps Section -->
                    <div class="bg-slate-800 rounded-lg border border-slate-600 overflow-hidden">
                        <div class="p-4 bg-slate-700 border-b border-slate-600">
                            <h3 class="text-lg font-semibold text-white">Station Locations Map</h3>
                        </div>
                        <div id="stationsMap" class="h-96 bg-gray-300"></div>
                    </div>
                </div>
            </div>

            <!-- Add Station Modal -->
            <div x-show="showAddStationModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                 @click.self="showAddStationModal = false">
                <div class="bg-slate-800 rounded-lg p-6 w-full max-w-md mx-4 border border-slate-600">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-white">Add New Station</h3>
                        <button @click="showAddStationModal = false" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="addStation()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Station Name</label>
                            <input x-model="newStation.name" type="text" required
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Address</label>
                            <input x-model="newStation.address" type="text" required
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Latitude</label>
                                <input x-model="newStation.lat" type="number" step="any" required
                                       class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Longitude</label>
                                <input x-model="newStation.lng" type="number" step="any" required
                                       class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Charging Slots</label>
                                <input x-model="newStation.slots" type="number" min="1" required
                                       class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Price/Hour ($)</label>
                                <input x-model="newStation.price" type="number" min="0" step="0.01" required
                                       class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" @click="showAddStationModal = false"
                                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                                Add Station
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Analytics Section -->
            <div x-show="activeSection === 'analytics'" class="w-full h-full bg-slate-700 p-6 overflow-y-auto">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-2xl font-bold text-white mb-6">Analytics & Reports</h2>
                    <p class="text-gray-400 mb-4">View system analytics and generate reports.</p>

                    <div class="bg-slate-800 rounded-lg p-8 border border-slate-600 text-center">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2H3z"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-white mb-2">Analytics Dashboard</h3>
                        <p class="text-gray-400">Analytics and reporting features coming soon.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function approveUser(user) {
            if (confirm(`Approve user ${user.name}?`)) {
                // Here you would make an API call to approve the user
                alert(`User ${user.name} has been approved!`);
                // Remove from pending list (in real app, refresh from API)
                const index = this.pendingUsers.findIndex(u => u.id === user.id);
                if (index > -1) {
                    this.pendingUsers.splice(index, 1);
                }
            }
        }

        function rejectUser(user) {
            if (confirm(`Reject user ${user.name}?`)) {
                // Here you would make an API call to reject the user
                alert(`User ${user.name} has been rejected.`);
                // Remove from pending list (in real app, refresh from API)
                const index = this.pendingUsers.findIndex(u => u.id === user.id);
                if (index > -1) {
                    this.pendingUsers.splice(index, 1);
                }
            }
        }

        // Reservation Management Functions
        function confirmReservation(reservation) {
            if (confirm(`Confirm reservation #${reservation.id} for ${reservation.user}?`)) {
                reservation.status = 'confirmed';
                alert(`Reservation #${reservation.id} has been confirmed!`);
                // Here you would make an API call to update the reservation
            }
        }

        function cancelReservation(reservation) {
            if (confirm(`Cancel reservation #${reservation.id} for ${reservation.user}?`)) {
                reservation.status = 'cancelled';
                alert(`Reservation #${reservation.id} has been cancelled.`);
                // Here you would make an API call to cancel the reservation
            }
        }

        function completeReservation(reservation) {
            if (confirm(`Mark reservation #${reservation.id} as completed?`)) {
                reservation.status = 'completed';
                alert(`Reservation #${reservation.id} marked as completed!`);
                // Here you would make an API call to complete the reservation
            }
        }

        // Station Management Functions
        function addStation() {
            if (confirm(`Add new station "${this.newStation.name}"?`)) {
                const newId = Math.max(...this.stations.map(s => s.id)) + 1;
                this.stations.push({
                    id: newId,
                    name: this.newStation.name,
                    address: this.newStation.address,
                    lat: parseFloat(this.newStation.lat),
                    lng: parseFloat(this.newStation.lng),
                    slots: parseInt(this.newStation.slots),
                    available: parseInt(this.newStation.slots),
                    price: parseFloat(this.newStation.price),
                    status: 'active'
                });

                // Reset form
                this.newStation = { name: '', address: '', lat: '', lng: '', slots: 4, price: 25 };
                this.showAddStationModal = false;

                alert(`Station "${this.newStation.name}" has been added successfully!`);
                // Here you would make an API call to add the station

                // Refresh map if loaded
                if (window.stationsMap) {
                    initStationsMap();
                }
            }
        }

        // Initialize Google Maps for stations
        function initStationsMap() {
            const mapElement = document.getElementById('stationsMap');
            if (!mapElement || !window.google) return;

            const map = new google.maps.Map(mapElement, {
                zoom: 10,
                center: { lat: 40.7128, lng: -74.0060 }, // Default to NYC
                styles: [
                    { elementType: 'geometry', stylers: [{ color: '#1f2937' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#1f2937' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#8fa2b7' }] },
                    {
                        featureType: 'administrative.locality',
                        elementType: 'labels.text.fill',
                        stylers: [{ color: '#d59563' }]
                    },
                    {
                        featureType: 'poi',
                        elementType: 'labels.text.fill',
                        stylers: [{ color: '#d59563' }]
                    },
                    {
                        featureType: 'poi.park',
                        elementType: 'geometry',
                        stylers: [{ color: '#263c3f' }]
                    },
                    {
                        featureType: 'poi.park',
                        elementType: 'labels.text.fill',
                        stylers: [{ color: '#6b9a76' }]
                    },
                    {
                        featureType: 'road',
                        elementType: 'geometry',
                        stylers: [{ color: '#38414e' }]
                    },
                    {
                        featureType: 'road',
                        elementType: 'geometry.stroke',
                        stylers: [{ color: '#212a37' }]
                    },
                    {
                        featureType: 'road',
                        elementType: 'labels.text.fill',
                        stylers: [{ color: '#9ca5b3' }]
                    },
                    {
                        featureType: 'road.highway',
                        elementType: 'geometry',
                        stylers: [{ color: '#746855' }]
                    },
                    {
                        featureType: 'road.highway',
                        elementType: 'geometry.stroke',
                        stylers: [{ color: '#1f2937' }]
                    },
                    {
                        featureType: 'road.highway',
                        elementType: 'labels.text.fill',
                        stylers: [{ color: '#f3d19c' }]
                    },
                    {
                        featureType: 'transit',
                        elementType: 'geometry',
                        stylers: [{ color: '#2f3948' }]
                    },
                    {
                        featureType: 'transit.station',
                        elementType: 'labels.text.fill',
                        stylers: [{ color: '#d59563' }]
                    },
                    {
                        featureType: 'water',
                        elementType: 'geometry',
                        stylers: [{ color: '#17263c' }]
                    },
                    {
                        featureType: 'water',
                        elementType: 'labels.text.fill',
                        stylers: [{ color: '#515c6d' }]
                    },
                    {
                        featureType: 'water',
                        elementType: 'labels.text.stroke',
                        stylers: [{ color: '#17263c' }]
                    }
                ]
            });

            window.stationsMap = map;

            // Add markers for existing stations
            // This would typically come from the Alpine.js data
            const stations = [
                { id: 1, name: 'Downtown Station', lat: 40.7128, lng: -74.0060, status: 'active' },
                { id: 2, name: 'Mall Station', lat: 40.7589, lng: -73.9851, status: 'active' },
                { id: 3, name: 'Airport Station', lat: 40.6892, lng: -74.1445, status: 'maintenance' }
            ];

            stations.forEach(station => {
                const marker = new google.maps.Marker({
                    position: { lat: station.lat, lng: station.lng },
                    map: map,
                    title: station.name,
                    icon: {
                        url: station.status === 'active' ?
                            'data:image/svg+xml;base64,' + btoa(`<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #10b981;"><path d="M13 10V3L4 14h4v7l9-11h-4z"/></svg>`) :
                            'data:image/svg+xml;base64,' + btoa(`<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #f59e0b;"><path d="M13 10V3L4 14h4v7l9-11h-4z"/></svg>`)
                    }
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="color: #1f2937; padding: 8px;">
                            <h3 style="font-weight: bold; margin: 0 0 8px 0;">${station.name}</h3>
                            <p style="margin: 0; font-size: 14px;">Status: ${station.status}</p>
                        </div>
                    `
                });

                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            });
        }

        // Initialize map when stations section is shown
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                initStationsMap();
            }, 1000);
        });
    </script>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header -->
            <header class="bg-gray-800 px-6 py-4 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <h1 class="text-xl font-semibold text-white">Admin Panel</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input
                                type="text"
                                placeholder="Search..."
                                class="bg-gray-700 text-white px-4 py-2 rounded-lg pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>
                            </svg>
                        </div>
                        <button class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg font-medium transition-colors">
                            <svg class="w-5 h-5 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                            </svg>
                            Settings
                        </button>
                        <div class="text-sm text-gray-300">
                            admin
                            <svg class="w-4 h-4 inline ml-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="flex-1 p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-white mb-2">Admin Dashboard</h2>
                    <p class="text-gray-400">Manage your EVC system</p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Users -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-300 text-sm font-medium">Total Users</h3>
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span class="text-2xl font-bold text-white">{{ $totalUsers ?? 1247 }}</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="text-green-400">+12% from last month</span>
                        </div>
                    </div>

                    <!-- Active Bookings -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-300 text-sm font-medium">Active Bookings</h3>
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span class="text-2xl font-bold text-white">{{ $activeBookings ?? 89 }}</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="text-green-400">+8% from yesterday</span>
                        </div>
                    </div>

                    <!-- Total Stations -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-300 text-sm font-medium">Total Stations</h3>
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 10V3L4 14h4v7l9-11h-4z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span class="text-2xl font-bold text-white">{{ $totalStations ?? 156 }}</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="text-green-400">+3 new this month</span>
                        </div>
                    </div>

                    <!-- Monthly Revenue -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-300 text-sm font-medium">Monthly Revenue</h3>
                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span class="text-2xl font-bold text-white">${{ $monthlyRevenue ?? '12,450' }}</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="text-green-400">+15% from last month</span>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="flex space-x-1 mb-6" x-data="{ activeTab: 'users' }">
                    <button @click="activeTab = 'users'" :class="activeTab === 'users' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-6 py-2 rounded-lg font-medium transition-colors">
                        Users
                    </button>
                    <button @click="activeTab = 'stations'" :class="activeTab === 'stations' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-6 py-2 rounded-lg font-medium transition-colors">
                        Stations
                    </button>
                    <button @click="activeTab = 'bookings'" :class="activeTab === 'bookings' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-6 py-2 rounded-lg font-medium transition-colors">
                        Bookings
                    </button>
                    <button @click="activeTab = 'analytics'" :class="activeTab === 'analytics' ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-6 py-2 rounded-lg font-medium transition-colors">
                        Analytics
                    </button>
                </div>

                <!-- User Management Table -->
                <div x-show="activeTab === 'users'" class="bg-gray-800 rounded-lg border border-gray-700">
                    <div class="p-6 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">User Management</h3>
                            <button class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg font-medium transition-colors">
                                Add User
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Join Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Bookings</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                @forelse($users ?? [] as $user)
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-white">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $user->is_verified ? 'bg-green-500 text-white' : 'bg-gray-600 text-gray-300' }}">
                                            {{ $user->is_verified ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ $user->bookings_count ?? 0 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button class="text-gray-400 hover:text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <!-- Sample data when no users -->
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-white">John Doe</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">john.doe@email.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">2024-05-10</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">12</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button class="text-gray-400 hover:text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-white">Jane Smith</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">jane.smith@email.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">2024-05-09</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">8</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button class="text-gray-400 hover:text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-white">Mike Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">mike.johnson@email.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">2024-05-08</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-600 text-gray-300">Inactive</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">3</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button class="text-gray-400 hover:text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-white">Sarah Wilson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">sarah.wilson@email.com</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">2024-05-07</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">15</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button class="text-gray-400 hover:text-white">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 110 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Other tabs content -->
                <div x-show="activeTab === 'stations'" class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Charging Stations</h3>
                    <p class="text-gray-400">Manage charging stations and their status.</p>
                </div>

                <div x-show="activeTab === 'bookings'" class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Booking Management</h3>
                    <p class="text-gray-400">View and manage user bookings.</p>
                </div>

                <div x-show="activeTab === 'analytics'" class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Analytics & Reports</h3>
                    <p class="text-gray-400">View system analytics and generate reports.</p>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
