/**
 * Admin Reservation Management JavaScript
 * Hotel Senang Hati - Reservation Management Interface
 */

class ReservationManager {
    constructor() {
        this.apiBaseUrl = 'api/reservations';
        this.roomsApiUrl = 'api/rooms';
        this.guestsApiUrl = 'api/guests';
        this.reservations = [];
        this.rooms = [];
        this.guests = [];
        this.statistics = {};
        this.init();
    }

    async init() {
        // Check authentication
        if (!await this.checkAuth()) {
            window.location.href = 'staff-login.html';
            return;
        }

        // Initialize event listeners
        this.setupEventListeners();
        
        // Load initial data
        await this.loadReservations();
        await this.loadRooms();
        await this.loadGuests();
        
        // Hide spinner
        document.getElementById('spinner').classList.remove('show');
    }

    async checkAuth() {
        try {
            const response = await fetch('api/auth/check.php', {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                return data.status === 'success';
            }
            return false;
        } catch (error) {
            console.error('Auth check failed:', error);
            return false;
        }
    }

    setupEventListeners() {
        // Search functionality
        document.getElementById('searchReservations').addEventListener('input', (e) => {
            this.filterReservations();
        });

        // Status filter
        document.getElementById('filterStatus').addEventListener('change', (e) => {
            this.filterReservations();
        });

        // Date filters
        document.getElementById('filterCheckInFrom').addEventListener('change', (e) => {
            this.filterReservations();
        });

        document.getElementById('filterCheckInTo').addEventListener('change', (e) => {
            this.filterReservations();
        });

        // Add reservation form
        document.getElementById('addReservationForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addReservation();
        });

        // Edit reservation form
        document.getElementById('editReservationForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateReservation();
        });
    }

    async loadReservations() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.reservations = data.data.reservations;
                this.statistics = data.data.statistics;
                
                this.renderReservationsTable();
                this.updateStatistics();
            } else {
                throw new Error('Failed to load reservations');
            }
        } catch (error) {
            console.error('Error loading reservations:', error);
            this.showAlert('Error loading reservations', 'error');
        }
    }

    async loadRooms() {
        try {
            const response = await fetch(`${this.roomsApiUrl}/`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.rooms = data.data.rooms;
                this.populateRoomSelects();
            }
        } catch (error) {
            console.error('Error loading rooms:', error);
        }
    }

    async loadGuests() {
        try {
            const response = await fetch(`${this.guestsApiUrl}/`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.guests = data.data.guests;
                this.populateGuestSelects();
            }
        } catch (error) {
            console.error('Error loading guests:', error);
        }
    }

    async filterReservations() {
        const searchTerm = document.getElementById('searchReservations').value;
        const statusFilter = document.getElementById('filterStatus').value;
        const checkInFrom = document.getElementById('filterCheckInFrom').value;
        const checkInTo = document.getElementById('filterCheckInTo').value;

        try {
            const params = new URLSearchParams();
            if (searchTerm) params.append('search', searchTerm);
            if (statusFilter) params.append('status', statusFilter);
            if (checkInFrom) params.append('check_in_date', checkInFrom);
            if (checkInTo) params.append('check_out_date', checkInTo);

            const response = await fetch(`${this.apiBaseUrl}/?${params}`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.reservations = data.data.reservations;
                this.statistics = data.data.statistics;
                
                this.renderReservationsTable();
                this.updateStatistics();
            }
        } catch (error) {
            console.error('Error filtering reservations:', error);
        }
    }

    renderReservationsTable() {
        const tbody = document.getElementById('reservationsTableBody');
        tbody.innerHTML = '';

        this.reservations.forEach(reservation => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${reservation.reservation_id}</td>
                <td>${reservation.first_name} ${reservation.last_name}</td>
                <td>${reservation.email}</td>
                <td>${reservation.room_number}</td>
                <td>${reservation.check_in_formatted}</td>
                <td>${reservation.check_out_formatted}</td>
                <td>${reservation.nights} night${reservation.nights > 1 ? 's' : ''}</td>
                <td>
                    <span class="badge ${this.getStatusBadgeClass(reservation.status)}">${this.formatStatus(reservation.status)}</span>
                </td>
                <td>
                    <span class="badge ${this.getPaymentBadgeClass(reservation.payment_status)}">${this.formatPaymentStatus(reservation.payment_status)}</span>
                </td>
                <td>IDR ${this.formatPrice(reservation.total_amount)}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-primary" onclick="reservationManager.editReservation(${reservation.reservation_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="reservationManager.checkIn(${reservation.reservation_id})" 
                                ${reservation.status !== 'confirmed' ? 'disabled' : ''}>
                            <i class="fas fa-sign-in-alt"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="reservationManager.checkOut(${reservation.reservation_id})" 
                                ${reservation.status !== 'checked_in' ? 'disabled' : ''}>
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="reservationManager.deleteReservation(${reservation.reservation_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    updateStatistics() {
        document.getElementById('totalReservations').textContent = this.statistics.total_reservations || 0;
        document.getElementById('confirmedReservations').textContent = this.statistics.confirmed_reservations || 0;
        document.getElementById('checkedInReservations').textContent = this.statistics.checked_in_reservations || 0;
        document.getElementById('totalRevenue').textContent = 'IDR ' + this.formatPrice(this.statistics.total_revenue || 0);
    }

    populateRoomSelects() {
        const roomSelects = ['roomId', 'editRoomId'];
        roomSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Select Room</option>';
                this.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.room_id;
                    option.textContent = `${room.room_number} - ${this.formatRoomType(room.type)} (IDR ${this.formatPrice(room.price_per_night)})`;
                    select.appendChild(option);
                });
            }
        });
    }

    populateGuestSelects() {
        const guestSelects = ['guestId', 'editGuestId'];
        guestSelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Select Guest</option>';
                this.guests.forEach(guest => {
                    const option = document.createElement('option');
                    option.value = guest.guest_id;
                    option.textContent = `${guest.first_name} ${guest.last_name} (${guest.email})`;
                    select.appendChild(option);
                });
            }
        });
    }

    async addReservation() {
        const formData = {
            guest_id: document.getElementById('guestId').value,
            room_id: document.getElementById('roomId').value,
            check_in_date: document.getElementById('checkInDate').value,
            check_out_date: document.getElementById('checkOutDate').value,
            total_amount: document.getElementById('totalAmount').value,
            status: document.getElementById('reservationStatus').value,
            payment_status: document.getElementById('paymentStatus').value,
            special_requests: document.getElementById('specialRequests').value
        };

        try {
            const response = await fetch(`${this.apiBaseUrl}/`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Reservation added successfully', 'success');
                this.closeModal('addReservationModal');
                this.clearForm('addReservationForm');
                await this.loadReservations();
            } else {
                this.showAlert(data.message || 'Failed to add reservation', 'error');
            }
        } catch (error) {
            console.error('Error adding reservation:', error);
            this.showAlert('Error adding reservation', 'error');
        }
    }

    async editReservation(reservationId) {
        const reservation = this.reservations.find(r => r.reservation_id == reservationId);
        if (!reservation) return;

        // Populate edit form
        document.getElementById('editReservationId').value = reservation.reservation_id;
        document.getElementById('editGuestId').value = reservation.guest_id;
        document.getElementById('editRoomId').value = reservation.room_id;
        document.getElementById('editCheckInDate').value = reservation.check_in_date.split(' ')[0];
        document.getElementById('editCheckOutDate').value = reservation.check_out_date.split(' ')[0];
        document.getElementById('editTotalAmount').value = reservation.total_amount;
        document.getElementById('editReservationStatus').value = reservation.status;
        document.getElementById('editPaymentStatus').value = reservation.payment_status;
        document.getElementById('editSpecialRequests').value = reservation.special_requests || '';

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editReservationModal'));
        modal.show();
    }

    async updateReservation() {
        const reservationId = document.getElementById('editReservationId').value;
        const formData = {
            guest_id: document.getElementById('editGuestId').value,
            room_id: document.getElementById('editRoomId').value,
            check_in_date: document.getElementById('editCheckInDate').value,
            check_out_date: document.getElementById('editCheckOutDate').value,
            total_amount: document.getElementById('editTotalAmount').value,
            status: document.getElementById('editReservationStatus').value,
            payment_status: document.getElementById('editPaymentStatus').value,
            special_requests: document.getElementById('editSpecialRequests').value
        };

        try {
            const response = await fetch(`${this.apiBaseUrl}/${reservationId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Reservation updated successfully', 'success');
                this.closeModal('editReservationModal');
                await this.loadReservations();
            } else {
                this.showAlert(data.message || 'Failed to update reservation', 'error');
            }
        } catch (error) {
            console.error('Error updating reservation:', error);
            this.showAlert('Error updating reservation', 'error');
        }
    }

    async deleteReservation(reservationId) {
        if (!confirm('Are you sure you want to delete this reservation?')) {
            return;
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/${reservationId}`, {
                method: 'DELETE',
                credentials: 'include'
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Reservation deleted successfully', 'success');
                await this.loadReservations();
            } else {
                this.showAlert(data.message || 'Failed to delete reservation', 'error');
            }
        } catch (error) {
            console.error('Error deleting reservation:', error);
            this.showAlert('Error deleting reservation', 'error');
        }
    }

    async checkIn(reservationId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/${reservationId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ status: 'checked_in' })
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Guest checked in successfully', 'success');
                await this.loadReservations();
            } else {
                this.showAlert(data.message || 'Failed to check in guest', 'error');
            }
        } catch (error) {
            console.error('Error checking in guest:', error);
            this.showAlert('Error checking in guest', 'error');
        }
    }

    async checkOut(reservationId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/${reservationId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ status: 'checked_out' })
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Guest checked out successfully', 'success');
                await this.loadReservations();
            } else {
                this.showAlert(data.message || 'Failed to check out guest', 'error');
            }
        } catch (error) {
            console.error('Error checking out guest:', error);
            this.showAlert('Error checking out guest', 'error');
        }
    }

    // Utility methods
    formatStatus(status) {
        const statuses = {
            'confirmed': 'Confirmed',
            'checked_in': 'Checked In',
            'checked_out': 'Checked Out',
            'cancelled': 'Cancelled',
            'no_show': 'No Show'
        };
        return statuses[status] || status;
    }

    formatPaymentStatus(status) {
        const statuses = {
            'pending': 'Pending',
            'paid': 'Paid',
            'refunded': 'Refunded'
        };
        return statuses[status] || status;
    }

    formatRoomType(type) {
        const types = {
            'standard': 'Standard',
            'deluxe': 'Deluxe',
            'suite': 'Suite',
            'presidential': 'Presidential'
        };
        return types[type] || type;
    }

    getStatusBadgeClass(status) {
        const classes = {
            'confirmed': 'bg-primary',
            'checked_in': 'bg-success',
            'checked_out': 'bg-info',
            'cancelled': 'bg-danger',
            'no_show': 'bg-warning'
        };
        return classes[status] || 'bg-secondary';
    }

    getPaymentBadgeClass(status) {
        const classes = {
            'pending': 'bg-warning',
            'paid': 'bg-success',
            'refunded': 'bg-info'
        };
        return classes[status] || 'bg-secondary';
    }

    formatPrice(price) {
        return new Intl.NumberFormat('id-ID').format(price);
    }

    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert at top of container
        const container = document.querySelector('.container-fluid.py-5 .container');
        container.insertBefore(alertDiv, container.firstChild);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    closeModal(modalId) {
        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
        if (modal) {
            modal.hide();
        }
    }

    clearForm(formId) {
        document.getElementById(formId).reset();
    }
}

// Initialize reservation manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.reservationManager = new ReservationManager();
});
