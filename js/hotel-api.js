/**
 * Hotel API Client
 * Hotel Senang Hati - Frontend API Integration
 */

class HotelAPI {
    constructor() {
        this.baseURL = window.location.origin + '/api';
        this.isAuthenticated = false;
        this.currentUser = null;
        this.init();
    }
    
    /**
     * Initialize API client
     */
    async init() {
        await this.checkAuth();
    }
    
    /**
     * Make HTTP request with error handling
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include' // Include cookies for session
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
            
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }
    
    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        
        return this.request(url, {
            method: 'GET'
        });
    }
    
    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }
    
    // ========== AUTHENTICATION ==========
    
    /**
     * Check authentication status
     */
    async checkAuth() {
        try {
            const response = await this.get('/auth/check');
            this.isAuthenticated = response.data.authenticated;
            this.currentUser = response.data.authenticated ? response.data : null;
            return this.isAuthenticated;
        } catch (error) {
            this.isAuthenticated = false;
            this.currentUser = null;
            return false;
        }
    }
    
    /**
     * Staff login
     */
    async login(username, password) {
        try {
            const response = await this.post('/auth/login', { username, password });
            this.isAuthenticated = true;
            this.currentUser = response.data;
            return response;
        } catch (error) {
            this.isAuthenticated = false;
            this.currentUser = null;
            throw error;
        }
    }
    
    /**
     * Staff logout
     */
    async logout() {
        try {
            await this.post('/auth/logout');
            this.isAuthenticated = false;
            this.currentUser = null;
            return true;
        } catch (error) {
            console.error('Logout error:', error);
            return false;
        }
    }
    
    /**
     * Get current user profile
     */
    async getProfile() {
        return this.get('/auth/profile');
    }
    
    // ========== ROOMS ==========
    
    /**
     * Get all rooms
     */
    async getRooms(filters = {}) {
        return this.get('/rooms', filters);
    }
    
    /**
     * Get room by ID
     */
    async getRoom(roomId) {
        return this.get(`/rooms/${roomId}`);
    }
    
    /**
     * Check room availability
     */
    async checkAvailability(checkIn, checkOut, roomType = null, capacity = null) {
        const params = { check_in: checkIn, check_out: checkOut };
        if (roomType) params.room_type = roomType;
        if (capacity) params.capacity = capacity;
        
        return this.get('/rooms/available', params);
    }
    
    /**
     * Create new room
     */
    async createRoom(roomData) {
        return this.post('/rooms', roomData);
    }
    
    /**
     * Update room
     */
    async updateRoom(roomId, roomData) {
        return this.put(`/rooms/${roomId}`, roomData);
    }
    
    /**
     * Update room status
     */
    async updateRoomStatus(roomId, status) {
        return this.put(`/rooms/${roomId}/status`, { status });
    }
    
    /**
     * Delete room
     */
    async deleteRoom(roomId) {
        return this.delete(`/rooms/${roomId}`);
    }
    
    /**
     * Get occupancy statistics
     */
    async getOccupancyStats() {
        return this.get('/rooms/occupancy');
    }
    
    // ========== RESERVATIONS ==========
    
    /**
     * Get all reservations
     */
    async getReservations(filters = {}) {
        return this.get('/reservations', filters);
    }
    
    /**
     * Get reservation by ID
     */
    async getReservation(reservationId) {
        return this.get(`/reservations/${reservationId}`);
    }
    
    /**
     * Get reservation by code
     */
    async getReservationByCode(reservationCode) {
        return this.get(`/reservations/code/${reservationCode}`);
    }
    
    /**
     * Create new reservation
     */
    async createReservation(reservationData) {
        return this.post('/reservations', reservationData);
    }
    
    /**
     * Update reservation
     */
    async updateReservation(reservationId, reservationData) {
        return this.put(`/reservations/${reservationId}`, reservationData);
    }
    
    /**
     * Update reservation status
     */
    async updateReservationStatus(reservationId, status, notes = null) {
        const data = { status };
        if (notes) data.notes = notes;
        return this.put(`/reservations/${reservationId}/status`, data);
    }
    
    /**
     * Cancel reservation
     */
    async cancelReservation(reservationId, reason = null) {
        const data = {};
        if (reason) data.reason = reason;
        return this.put(`/reservations/${reservationId}/cancel`, data);
    }
    
    /**
     * Get today's check-ins
     */
    async getTodayCheckIns() {
        return this.get('/reservations/checkins');
    }
    
    /**
     * Get today's check-outs
     */
    async getTodayCheckOuts() {
        return this.get('/reservations/checkouts');
    }
    
    /**
     * Get reservation statistics
     */
    async getReservationStats(startDate = null, endDate = null) {
        const params = {};
        if (startDate) params.start_date = startDate;
        if (endDate) params.end_date = endDate;
        return this.get('/reservations/stats', params);
    }
    
    // ========== GUESTS ==========
    
    /**
     * Get all guests
     */
    async getGuests(filters = {}) {
        return this.get('/guests', filters);
    }
    
    /**
     * Get guest by ID
     */
    async getGuest(guestId) {
        return this.get(`/guests/${guestId}`);
    }
    
    /**
     * Get guest by email
     */
    async getGuestByEmail(email) {
        return this.get(`/guests/email/${encodeURIComponent(email)}`);
    }
    
    /**
     * Create new guest
     */
    async createGuest(guestData) {
        return this.post('/guests', guestData);
    }
    
    /**
     * Update guest
     */
    async updateGuest(guestId, guestData) {
        return this.put(`/guests/${guestId}`, guestData);
    }
    
    /**
     * Search guests
     */
    async searchGuests(query) {
        return this.get('/guests/search', { q: query });
    }
    
    /**
     * Get guest reservations
     */
    async getGuestReservations(guestId) {
        return this.get(`/guests/${guestId}/reservations`);
    }
    
    /**
     * Get VIP guests
     */
    async getVipGuests() {
        return this.get('/guests/vip');
    }
    
    // ========== DASHBOARD ==========
    
    /**
     * Get dashboard statistics
     */
    async getDashboardStats() {
        return this.get('/dashboard/stats');
    }
    
    /**
     * Get revenue overview
     */
    async getRevenueOverview() {
        return this.get('/dashboard/revenue');
    }
    
    /**
     * Get occupancy overview
     */
    async getOccupancyOverview() {
        return this.get('/dashboard/occupancy');
    }
    
    /**
     * Get today's activity
     */
    async getTodayActivity() {
        return this.get('/dashboard/activity');
    }
    
    /**
     * Get chart data
     */
    async getChartData(type, period = 30) {
        return this.get('/dashboard/charts', { type, period });
    }
    
    // ========== UTILITIES ==========
    
    /**
     * Test database connection
     */
    async testConnection() {
        return this.get('/test');
    }
    
    /**
     * Format currency (Indonesian Rupiah)
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
    
    /**
     * Format date
     */
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        
        return new Intl.DateTimeFormat('id-ID', { ...defaultOptions, ...options })
            .format(new Date(date));
    }
    
    /**
     * Calculate nights between dates
     */
    calculateNights(checkIn, checkOut) {
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const diffTime = Math.abs(end - start);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }
    
    /**
     * Generate reservation summary
     */
    generateReservationSummary(reservation) {
        const nights = this.calculateNights(reservation.check_in_date, reservation.check_out_date);
        
        return {
            ...reservation,
            nights_count: nights,
            formatted_total: this.formatCurrency(reservation.total_amount),
            formatted_checkin: this.formatDate(reservation.check_in_date),
            formatted_checkout: this.formatDate(reservation.check_out_date)
        };
    }
    
    /**
     * Show success notification
     */
    showSuccess(message) {
        // You can integrate with your notification system here
        console.log('✅ Success:', message);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }
    }
    
    /**
     * Show error notification
     */
    showError(message) {
        // You can integrate with your notification system here
        console.error('❌ Error:', message);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message
            });
        }
    }
    
    /**
     * Show loading state
     */
    showLoading(message = 'Loading...') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
    }
    
    /**
     * Hide loading state
     */
    hideLoading() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
    }
}

// Create global API instance
window.HotelAPI = new HotelAPI();

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HotelAPI;
}
