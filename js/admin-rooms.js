/**
 * Admin Room Management JavaScript
 * Hotel Senang Hati - Room Management Interface
 */

class RoomManager {
    constructor() {
        this.apiBaseUrl = 'api/rooms';
        this.rooms = [];
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
        await this.loadRooms();
        
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
        document.getElementById('searchRooms').addEventListener('input', (e) => {
            this.filterRooms();
        });

        // Status filter
        document.getElementById('filterStatus').addEventListener('change', (e) => {
            this.filterRooms();
        });

        // Add room form
        document.getElementById('addRoomForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.addRoom();
        });

        // Edit room form
        document.getElementById('editRoomForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateRoom();
        });
    }

    async loadRooms() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.rooms = data.data.rooms;
                this.statistics = data.data.statistics;
                
                this.renderRoomsTable();
                this.updateStatistics();
            } else {
                throw new Error('Failed to load rooms');
            }
        } catch (error) {
            console.error('Error loading rooms:', error);
            this.showAlert('Error loading rooms', 'error');
        }
    }

    async filterRooms() {
        const searchTerm = document.getElementById('searchRooms').value;
        const statusFilter = document.getElementById('filterStatus').value;

        try {
            const params = new URLSearchParams();
            if (searchTerm) params.append('search', searchTerm);
            if (statusFilter) params.append('status', statusFilter);

            const response = await fetch(`${this.apiBaseUrl}/?${params}`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                this.rooms = data.data.rooms;
                this.statistics = data.data.statistics;
                
                this.renderRoomsTable();
                this.updateStatistics();
            }
        } catch (error) {
            console.error('Error filtering rooms:', error);
        }
    }

    renderRoomsTable() {
        const tbody = document.getElementById('roomsTableBody');
        tbody.innerHTML = '';

        this.rooms.forEach(room => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${room.room_number}</td>
                <td>
                    <span class="badge bg-secondary">${this.formatRoomType(room.type)}</span>
                </td>
                <td>${room.floor}</td>
                <td>
                    <span class="badge ${this.getStatusBadgeClass(room.status)}">${this.formatStatus(room.status)}</span>
                </td>
                <td>IDR ${this.formatPrice(room.price_per_night)}</td>
                <td>${room.capacity} Guest${room.capacity > 1 ? 's' : ''}</td>
                <td>${room.last_cleaned_formatted || 'Never'}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-primary" onclick="roomManager.editRoom(${room.room_id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-success" onclick="roomManager.markCleaned(${room.room_id})" 
                                title="Mark as Cleaned">
                            <i class="fas fa-broom"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="roomManager.deleteRoom(${room.room_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    updateStatistics() {
        document.getElementById('totalRooms').textContent = this.statistics.total_rooms || 0;
        document.getElementById('availableRooms').textContent = this.statistics.available_rooms || 0;
        document.getElementById('occupiedRooms').textContent = this.statistics.occupied_rooms || 0;
        document.getElementById('maintenanceRooms').textContent = this.statistics.maintenance_rooms || 0;
    }

    async addRoom() {
        const formData = {
            room_number: document.getElementById('roomNumber').value,
            type: document.getElementById('roomType').value,
            floor: document.getElementById('floor').value,
            capacity: document.getElementById('capacity').value,
            price_per_night: document.getElementById('pricePerNight').value,
            status: document.getElementById('roomStatus').value,
            amenities: document.getElementById('amenities').value,
            description: document.getElementById('description').value
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
                this.showAlert('Room added successfully', 'success');
                this.closeModal('addRoomModal');
                this.clearForm('addRoomForm');
                await this.loadRooms();
            } else {
                this.showAlert(data.message || 'Failed to add room', 'error');
            }
        } catch (error) {
            console.error('Error adding room:', error);
            this.showAlert('Error adding room', 'error');
        }
    }

    async editRoom(roomId) {
        const room = this.rooms.find(r => r.room_id == roomId);
        if (!room) return;

        // Populate edit form
        document.getElementById('editRoomId').value = room.room_id;
        document.getElementById('editRoomNumber').value = room.room_number;
        document.getElementById('editRoomType').value = room.type;
        document.getElementById('editFloor').value = room.floor;
        document.getElementById('editCapacity').value = room.capacity;
        document.getElementById('editPricePerNight').value = room.price_per_night;
        document.getElementById('editRoomStatus').value = room.status;
        document.getElementById('editAmenities').value = room.amenities || '';
        document.getElementById('editDescription').value = room.description || '';

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editRoomModal'));
        modal.show();
    }

    async updateRoom() {
        const roomId = document.getElementById('editRoomId').value;
        const formData = {
            room_number: document.getElementById('editRoomNumber').value,
            type: document.getElementById('editRoomType').value,
            floor: document.getElementById('editFloor').value,
            capacity: document.getElementById('editCapacity').value,
            price_per_night: document.getElementById('editPricePerNight').value,
            status: document.getElementById('editRoomStatus').value,
            amenities: document.getElementById('editAmenities').value,
            description: document.getElementById('editDescription').value
        };

        try {
            const response = await fetch(`${this.apiBaseUrl}/${roomId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Room updated successfully', 'success');
                this.closeModal('editRoomModal');
                await this.loadRooms();
            } else {
                this.showAlert(data.message || 'Failed to update room', 'error');
            }
        } catch (error) {
            console.error('Error updating room:', error);
            this.showAlert('Error updating room', 'error');
        }
    }

    async deleteRoom(roomId) {
        if (!confirm('Are you sure you want to delete this room?')) {
            return;
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/${roomId}`, {
                method: 'DELETE',
                credentials: 'include'
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Room deleted successfully', 'success');
                await this.loadRooms();
            } else {
                this.showAlert(data.message || 'Failed to delete room', 'error');
            }
        } catch (error) {
            console.error('Error deleting room:', error);
            this.showAlert('Error deleting room', 'error');
        }
    }

    async markCleaned(roomId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/${roomId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({ 
                    status: 'available',
                    last_cleaned: new Date().toISOString().split('T')[0]
                })
            });

            const data = await response.json();

            if (response.ok) {
                this.showAlert('Room marked as cleaned', 'success');
                await this.loadRooms();
            } else {
                this.showAlert(data.message || 'Failed to mark room as cleaned', 'error');
            }
        } catch (error) {
            console.error('Error marking room as cleaned:', error);
            this.showAlert('Error marking room as cleaned', 'error');
        }
    }

    // Utility methods
    formatRoomType(type) {
        const types = {
            'standard': 'Standard',
            'deluxe': 'Deluxe',
            'suite': 'Suite',
            'presidential': 'Presidential'
        };
        return types[type] || type;
    }

    formatStatus(status) {
        const statuses = {
            'available': 'Available',
            'occupied': 'Occupied',
            'maintenance': 'Maintenance',
            'cleaning': 'Cleaning',
            'out-of-order': 'Out of Order'
        };
        return statuses[status] || status;
    }

    getStatusBadgeClass(status) {
        const classes = {
            'available': 'bg-success',
            'occupied': 'bg-primary',
            'maintenance': 'bg-warning',
            'cleaning': 'bg-info',
            'out-of-order': 'bg-danger'
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

// Initialize room manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.roomManager = new RoomManager();
});
