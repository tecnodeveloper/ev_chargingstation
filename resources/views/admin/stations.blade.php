<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Station Management - EVC Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Fallback Tailwind CSS for development -->
    @vite(['resources/css/app.css'])
    <!-- Fallback CDN for development only -->
    @if(config('app.env') === 'local')
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Ensure Google Maps container has proper styling */
        #map {
            min-height: 384px !important; /* h-96 = 384px */
            background-color: #374151 !important; /* slate-700 */
        }

        /* Google Maps controls styling */
        .gm-style-cc {
            filter: invert(1) hue-rotate(180deg) brightness(0.8);
        }
    </style>
    <!-- Google Maps API with proper async loading -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places,marker&loading=async&callback=initGoogleMaps"
        onerror="console.error('❌ Failed to load Google Maps API - Check billing and API enablement')"></script>

    <script>
        // Global Google Maps callback
        window.initGoogleMaps = function() {
            console.log('✅ Google Maps API loaded successfully');
            window.googleMapsLoaded = true;
            // Trigger map initialization if Alpine.js component is ready
            if (window.stationManagement) {
                console.log('🔄 Triggering map initialization from callback');
                window.stationManagement.initMap();
            } else {
                console.log('⏳ Alpine.js component not ready yet');
            }
        };

        // Global error handler for Google Maps
        window.gm_authFailure = function() {
            console.error('🔐 Google Maps authentication failed');
            if (window.stationManagement) {
                window.stationManagement.handleMapError('Authentication failed - Check API key and billing');
            }
        };

        // Handle billing errors
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('BillingNotEnabledMapError')) {
                console.error('💳 Google Maps billing not enabled');
                if (window.stationManagement) {
                    window.stationManagement.handleMapError('Billing not enabled for Google Maps API');
                }
            }
        });

    // Debug: Check if Google Maps script is loading
    console.log('🚀 Station Management page loaded');
    </script>
</head>
<body class="bg-slate-900 text-white h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-slate-800 shadow-lg border-b border-slate-700">
        <div class="flex items-center justify-between px-6 py-4">
            <!-- Logo -->
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11 1l-8 9h5v12h6V10h5L11 1z"/>
                    </svg>
                </div>
                <div class="text-green-500 font-bold text-2xl tracking-wider">EVC Admin</div>
            </div>

            <!-- Navigation -->
            <nav class="flex items-center space-x-8">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 text-gray-300 hover:text-white font-medium transition-colors">
                    Dashboard
                </a>
                <a href="{{ route('admin.users') }}" class="px-4 py-2 text-gray-300 hover:text-white font-medium transition-colors">
                    EVC Users
                </a>
                <a href="{{ route('admin.bookings') }}" class="px-4 py-2 text-gray-300 hover:text-white font-medium transition-colors relative">
                    Booking Management
                    @php
                        $pendingCount = App\Models\Booking::where('status', 'pending')->count();
                    @endphp
                    @if($pendingCount > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ $pendingCount > 9 ? '9+' : $pendingCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('admin.stations') }}" class="px-4 py-2 text-green-500 border-b-2 border-green-500 font-medium">
                    Add Charging Station
                </a>
            </nav>

            <!-- Admin Profile -->
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2 bg-slate-700 px-4 py-2 rounded-lg">
                    <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold text-sm">A</span>
                    </div>
                    <div class="text-left">
                        <div class="font-medium">Admin</div>
                        <div class="text-xs text-gray-400">admin@evc.com</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-10 h-10 bg-red-500/20 hover:bg-red-500/30 border border-red-500/40 rounded-full flex items-center justify-center text-red-400 hover:text-red-300 transition-all duration-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 bg-slate-700 p-6" x-data="stationManagement()">
        <div class="max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Station Management</h1>
                <p class="text-gray-400">Add and manage EV charging stations with real-time updates</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Panel: Station Form -->
                <div class="bg-slate-800 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-white">Add New Station</h2>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-green-400 text-sm">Real-time Broadcasting</span>
                        </div>
                    </div>

                    <form @submit.prevent="addStation" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Station Name</label>
                            <input
                                type="text"
                                x-model="newStation.name"
                                required
                                class="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-green-500 focus:outline-none"
                                placeholder="e.g., Downtown Shopping Center"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Address</label>
                            <input
                                type="text"
                                x-model="newStation.address"
                                required
                                class="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-green-500 focus:outline-none"
                                placeholder="Enter station address"
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Latitude</label>
                                <input
                                    type="number"
                                    step="any"
                                    x-model="newStation.lat"
                                    required
                                    class="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-green-500 focus:outline-none"
                                    placeholder="e.g., 37.7749"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Longitude</label>
                                <input
                                    type="number"
                                    step="any"
                                    x-model="newStation.lng"
                                    required
                                    class="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-green-500 focus:outline-none"
                                    placeholder="e.g., -122.4194"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Price per Hour ($)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                x-model="newStation.price"
                                required
                                class="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-green-500 focus:outline-none"
                                placeholder="25.00"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                            <select x-model="newStation.status" class="w-full bg-slate-700 text-white px-4 py-2 rounded-lg border border-slate-600 focus:border-green-500 focus:outline-none">
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>

                        <div class="flex space-x-4">
                            <button
                                type="submit"
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-medium transition-colors"
                            >
                                Add Station
                            </button>
                            <button
                                type="button"
                                @click="clearForm()"
                                class="px-6 py-3 bg-slate-600 hover:bg-slate-500 text-white rounded-lg font-medium transition-colors"
                            >
                                Clear
                            </button>
                        </div>
                    </form>

                    <!-- Station Instructions -->
                    <div class="mt-6 p-4 bg-blue-500/20 border border-blue-500/30 rounded-lg">
                        <h3 class="text-blue-300 font-medium mb-2">📍 How to Add Stations</h3>
                        <p class="text-blue-200 text-sm">Enter the latitude and longitude coordinates manually or use online tools like Google Maps to find exact coordinates.</p>
                    </div>
                </div>

                <!-- Right Panel: Map -->
                <div class="bg-slate-800 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Station Location Visualizer</h2>
                    <div id="map" class="w-full h-96 rounded-lg bg-slate-700"></div>

                    <!-- Map Instructions -->
                    <div class="mt-4 text-sm text-gray-400">
                        <p>• Map visualization requires Google Maps API key</p>
                        <p>• Use coordinates: Latitude (e.g., 37.7749), Longitude (e.g., -122.4194)</p>
                        <p>• Find coordinates at: <a href="https://www.google.com/maps" target="_blank" class="text-blue-400 hover:text-blue-300">Google Maps</a></p>
                    </div>
                </div>
            </div>

            <!-- Existing Stations List -->
            <div class="mt-8 bg-slate-800 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-700">
                    <h2 class="text-xl font-bold text-white">Existing Stations</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-700">
                            <tr>
                                <th class="text-left py-3 px-6 text-gray-300 font-medium">Station</th>
                                <th class="text-left py-3 px-6 text-gray-300 font-medium">Address</th>
                                <th class="text-left py-3 px-6 text-gray-300 font-medium">Price/Hour</th>
                                <th class="text-left py-3 px-6 text-gray-300 font-medium">Status</th>
                                <th class="text-left py-3 px-6 text-gray-300 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="station in stations" :key="station.id">
                                <tr class="border-b border-slate-700 hover:bg-slate-750 transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-medium text-white" x-text="station.name"></div>
                                                <div class="text-sm text-gray-400" x-text="'ID: ' + station.id"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-gray-300" x-text="station.address"></td>
                                    <td class="py-4 px-6 text-gray-300" x-text="'$' + station.price"></td>
                                    <td class="py-4 px-6">
                                        <span
                                            class="px-2 py-1 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800': station.status === 'active',
                                                'bg-yellow-100 text-yellow-800': station.status === 'maintenance',
                                                'bg-red-100 text-red-800': station.status === 'offline'
                                            }"
                                            x-text="station.status"
                                        ></span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-2">
                                            <button
                                                @click="editStation(station)"
                                                class="text-blue-400 hover:text-blue-300 text-sm font-medium"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteStation(station.id)"
                                                class="text-red-400 hover:text-red-300 text-sm font-medium"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- No Stations Message -->
                <div x-show="stations.length === 0" class="p-8 text-center">
                    <div class="text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-lg font-medium">No stations found</p>
                        <p class="text-sm">Add your first charging station using the form above</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let map;
        let marker;

        function stationManagement() {
            return {
                stations: @json($stations ?? []),
                newStation: {
                    name: '',
                    address: '',
                    lat: '',
                    lng: '',
                    price: 25.00,
                    status: 'active'
                },

                init() {
                    this.loadStations();

                    // Store reference for Google Maps callback
                    window.stationManagement = this;

                    // Initialize map if Google Maps is already loaded
                    if (window.googleMapsLoaded || (typeof google !== 'undefined' && google.maps)) {
                        this.$nextTick(() => {
                            this.initMap();
                        });
                    } else {
                        // Wait for Google Maps to load
                        const checkGoogleMaps = setInterval(() => {
                            if (window.googleMapsLoaded || (typeof google !== 'undefined' && google.maps)) {
                                clearInterval(checkGoogleMaps);
                                this.$nextTick(() => {
                                    this.initMap();
                                });
                            }
                        }, 100);

                        // Timeout after 10 seconds
                        setTimeout(() => {
                            clearInterval(checkGoogleMaps);
                            if (!window.googleMapsLoaded && (typeof google === 'undefined' || !google.maps)) {
                                console.warn('Google Maps failed to load after 10 seconds');
                                this.initMap(); // This will show the fallback UI
                            }
                        }, 10000);
                    }
                },

                async loadStations() {
                    try {
                        const response = await fetch('/admin/api/stations');
                        if (response.ok) {
                            this.stations = await response.json();
                        }
                    } catch (error) {
                        console.error('Error loading stations:', error);
                    }
                },

                initMap() {
                    console.log('🗺️ Initializing Google Maps...');

                    // Show placeholder message if Google Maps is not available
                    if (typeof google === 'undefined' || !google.maps) {
                        console.warn('❌ Google Maps API not available');
                        const mapElement = document.getElementById('map');
                        if (mapElement) {
                            mapElement.innerHTML = `
                                <div class="flex items-center justify-center h-full bg-slate-700 rounded-lg border-2 border-dashed border-slate-500">
                                    <div class="text-center text-gray-400 p-8">
                                        <div class="text-6xl mb-4">🗺️</div>
                                        <h3 class="text-lg font-semibold mb-2">Google Maps Setup Required</h3>
                                        <p class="text-sm text-red-300">Current Error: Billing not enabled</p>
                                        <ol class="text-xs mt-2 text-left space-y-1">
                                            <li>1. Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-400">Google Cloud Console</a></li>
                                            <li>2. <strong>Enable billing</strong> for your project</li>
                                            <li>3. Enable "Maps JavaScript API"</li>
                                            <li>4. Add localhost:8000 to authorized domains</li>
                                        </ol>
                                        <p class="text-xs mt-3 text-yellow-400">Use manual coordinate entry below</p>
                                    </div>
                                </div>
                            `;
                        }
                        return;
                    }

                    console.log('✅ Google Maps API available, creating map...');

                    try {
                        const mapElement = document.getElementById('map');
                        if (!mapElement) {
                            console.error('❌ Map container element not found');
                            return;
                        }

                        map = new google.maps.Map(mapElement, {
                            zoom: 13,
                            center: { lat: 37.7749, lng: -122.4194 }, // San Francisco default
                            mapId: 'evc_admin_map', // Required for Advanced Markers
                            disableDefaultUI: false,
                            zoomControl: true,
                            mapTypeControl: false,
                            streetViewControl: false,
                            fullscreenControl: true,
                            styles: [
                                { elementType: 'geometry', stylers: [{ color: '#1f2937' }] },
                                { elementType: 'labels.text.stroke', stylers: [{ color: '#1f2937' }] },
                                { elementType: 'labels.text.fill', stylers: [{ color: '#8b5cf6' }] },
                                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#374151' }] },
                                { featureType: 'water', elementType:'geometry', stylers: [{ color: '#0f172a' }] }
                            ]
                        });

                        console.log('🗺️ Google Map created successfully');

                        // Add click listener for map
                        map.addListener('click', (event) => {
                            console.log('📍 Map clicked at:', event.latLng.lat(), event.latLng.lng());
                            this.selectLocation(event.latLng.lat(), event.latLng.lng());
                        });

                        // Add existing stations to map
                        this.addExistingStationsToMap();

                        console.log('✅ Map initialization completed successfully');

                    } catch (error) {
                        console.error('❌ Error initializing Google Maps:', error);
                        const mapElement = document.getElementById('map');
                        if (mapElement) {
                            let errorMessage = 'Failed to initialize map';
                            let instructions = 'Use manual coordinate entry below';

                            if (error.message && error.message.includes('Billing')) {
                                errorMessage = 'Billing Not Enabled';
                                instructions = `
                                    <div class="text-left mt-3">
                                        <p class="text-yellow-300 font-semibold mb-2">Required Steps:</p>
                                        <ol class="text-xs space-y-1">
                                            <li>1. Go to <a href="https://console.cloud.google.com/billing" target="_blank" class="text-blue-400">Google Cloud Billing</a></li>
                                            <li>2. Enable billing for your project</li>
                                            <li>3. Enable Maps JavaScript API</li>
                                            <li>4. Refresh this page</li>
                                        </ol>
                                        <p class="text-yellow-400 mt-3">Use manual coordinate entry below</p>
                                    </div>
                                `;
                            }

                            mapElement.innerHTML = `
                                <div class="flex items-center justify-center h-full bg-red-900/20 rounded-lg border-2 border-red-500/30">
                                    <div class="text-center text-red-300 p-6">
                                        <div class="text-5xl mb-3">🏦</div>
                                        <h3 class="text-lg font-semibold mb-2">${errorMessage}</h3>
                                        <p class="text-sm mb-2">${error.message || 'Unknown error'}</p>
                                        ${instructions}
                                    </div>
                                </div>
                            `;
                        }
                    }
                },

                selectLocation(lat, lng) {
                    this.newStation.lat = lat;
                    this.newStation.lng = lng;

                    // Remove previous marker
                    if (marker) {
                        marker.setMap(null);
                    }

                    // Add new marker using modern API with fallback
                    try {
                        // Try using AdvancedMarkerElement if available
                        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                            const markerElement = document.createElement('div');
                            markerElement.innerHTML = `
                                <div style="background: #ef4444; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;">
                                    <span style="color: white; font-size: 14px;">📍</span>
                                </div>
                            `;

                            marker = new google.maps.marker.AdvancedMarkerElement({
                                position: { lat: lat, lng: lng },
                                map: map,
                                content: markerElement,
                                title: 'New Station Location'
                            });
                        } else {
                            // Fallback to legacy marker
                            marker = new google.maps.Marker({
                                position: { lat: lat, lng: lng },
                                map: map,
                                icon: {
                                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="10" fill="#ef4444" stroke="white" stroke-width="2"/>
                                            <text x="12" y="16" text-anchor="middle" fill="white" font-size="12">📍</text>
                                        </svg>
                                    `),
                                    scaledSize: new google.maps.Size(30, 30)
                                }
                            });
                        }
                    } catch (error) {
                        console.error('Error creating marker:', error);
                        // Fallback marker creation
                        marker = new google.maps.Marker({
                            position: { lat: lat, lng: lng },
                            map: map
                        });
                    }
                },

                addExistingStationsToMap() {
                    this.stations.forEach(station => {
                        new google.maps.Marker({
                            position: { lat: parseFloat(station.lat), lng: parseFloat(station.lng) },
                            map: map,
                            title: station.name,
                            icon: {
                                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#10b981"/>
                                    </svg>
                                `),
                                scaledSize: new google.maps.Size(40, 40)
                            }
                        });
                    });
                },

                async addStation() {
                    // Validate form inputs
                    if (!this.newStation.name.trim()) {
                        alert('Please enter a station name');
                        return;
                    }

                    if (!this.newStation.address.trim()) {
                        alert('Please enter the station address');
                        return;
                    }

                    if (!this.newStation.lat || !this.newStation.lng) {
                        alert('Please enter latitude and longitude coordinates');
                        return;
                    }

                    if (isNaN(this.newStation.lat) || isNaN(this.newStation.lng)) {
                        alert('Latitude and longitude must be valid numbers');
                        return;
                    }

                    try {
                        // Prepare data with correct field names for API
                        const stationData = {
                            name: this.newStation.name,
                            address: this.newStation.address,
                            latitude: parseFloat(this.newStation.lat),
                            longitude: parseFloat(this.newStation.lng),
                            price_per_hour: parseFloat(this.newStation.price),
                            status: this.newStation.status
                        };

                        console.log('Sending station data:', stationData);

                        const response = await fetch('/admin/api/stations', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(stationData)
                        });

                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);

                        if (response.ok) {
                            const responseText = await response.text();
                            console.log('Raw response:', responseText);

                            try {
                                const station = JSON.parse(responseText);
                                this.stations.push(station);
                                this.clearForm();
                                if (marker) {
                                    if (marker.setMap) {
                                        marker.setMap(null);
                                    } else if (marker.map) {
                                        marker.map = null;
                                    }
                                    marker = null;
                                }
                                alert('Station added successfully!');
                                this.addExistingStationsToMap();
                            } catch (parseError) {
                                console.error('JSON Parse Error:', parseError);
                                console.log('Response was not JSON:', responseText);
                                alert('Station may have been added, but response format was unexpected. Please refresh the page.');
                            }
                        } else {
                            const errorText = await response.text();
                            console.error('Server Error:', errorText);
                            throw new Error(`Server returned ${response.status}: ${errorText}`);
                        }
                    } catch (error) {
                        console.error('Error adding station:', error);
                        alert(`Error adding station: ${error.message}`);
                    }
                },

                clearForm() {
                    this.newStation = {
                        name: '',
                        address: '',
                        lat: '',
                        lng: '',
                        price: 25.00,
                        status: 'active'
                    };
                    if (marker) {
                        marker.setMap(null);
                        marker = null;
                    }
                },

                handleMapError(errorMessage) {
                    console.error('🗺️ Handling map error:', errorMessage);
                    const mapElement = document.getElementById('map');
                    if (mapElement) {
                        mapElement.innerHTML = `
                            <div class="flex items-center justify-center h-full bg-red-900/20 rounded-lg border-2 border-red-500/30">
                                <div class="text-center text-red-300 p-6">
                                    <div class="text-5xl mb-3">💳</div>
                                    <h3 class="text-lg font-semibold mb-2">Google Maps Billing Required</h3>
                                    <p class="text-sm mb-3">${errorMessage}</p>
                                    <div class="text-left">
                                        <p class="text-yellow-300 font-semibold mb-2">Required Steps:</p>
                                        <ol class="text-xs space-y-1">
                                            <li>1. Go to <a href="https://console.cloud.google.com/billing" target="_blank" class="text-blue-400 hover:text-blue-300">Google Cloud Console</a></li>
                                            <li>2. Enable billing for your project</li>
                                            <li>3. Enable Maps JavaScript API</li>
                                            <li>4. Refresh this page</li>
                                        </ol>
                                        <p class="text-yellow-400 mt-3 text-center">💡 Use manual coordinate entry below</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                },

                editStation(station) {
                    // Implement edit functionality
                    console.log('Edit station:', station);
                },

                async deleteStation(stationId) {
                    if (confirm('Are you sure you want to delete this station?')) {
                        try {
                            const response = await fetch(`/admin/api/stations/${stationId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });

                            if (response.ok) {
                                this.stations = this.stations.filter(s => s.id !== stationId);
                                alert('Station deleted successfully');
                            } else {
                                throw new Error('Failed to delete station');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Error deleting station');
                        }
                    }
                }
            }
        }
    </script>
</body>
</html>
