/**
 * Hotel Management System JavaScript
 * Hotel Senang Hati - Room Booking & Management
 */

// ===== HOTEL SYSTEM CONFIGURATION =====
const HOTEL_CONFIG = {
    name: "Hotel Senang Hati",
    checkInTime: "14:00",
    checkOutTime: "12:00",
    currency: "IDR",
    timezone: "Asia/Jakarta"
};

// ===== ROOM TYPES & PRICING =====
const ROOM_TYPES = {
    standard: {
        name: "Standard Room",
        capacity: 2,
        basePrice: 500000,
        features: ["AC", "TV", "WiFi", "Kamar Mandi Dalam"],
        image: "img/room-standard.jpg"
    },
    deluxe: {
        name: "Deluxe Room", 
        capacity: 3,
        basePrice: 750000,
        features: ["AC", "TV LCD", "WiFi", "Mini Bar", "Balkon"],
        image: "img/room-deluxe.jpg"
    },
    suite: {
        name: "Suite Room",
        capacity: 4,
        basePrice: 1200000,
        features: ["AC", "Smart TV", "WiFi", "Mini Bar", "Jacuzzi", "Living Room"],
        image: "img/room-suite.jpg"
    },
    presidential: {
        name: "Presidential Suite",
        capacity: 6,
        basePrice: 2500000,
        features: ["AC", "Smart TV", "WiFi", "Mini Bar", "Jacuzzi", "Living Room", "Kitchen", "Butler Service"],
        image: "img/room-presidential.jpg"
    }
};

// ===== UTILITY FUNCTIONS =====
class HotelUtils {
    static formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    static formatDate(date) {
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    }

    static calculateDays(checkin, checkout) {
        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);
        const diffTime = Math.abs(checkoutDate - checkinDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    }

    static generateBookingCode() {
        const prefix = "HSH";
        const timestamp = Date.now().toString().slice(-6);
        const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
        return `${prefix}${timestamp}${random}`;
    }

    static validateDates(checkin, checkout) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);
        
        if (checkinDate < today) {
            return { valid: false, message: "Tanggal check-in tidak boleh kurang dari hari ini" };
        }
        
        if (checkoutDate <= checkinDate) {
            return { valid: false, message: "Tanggal check-out harus setelah check-in" };
        }
        
        return { valid: true };
    }
}

// ===== ROOM AVAILABILITY SYSTEM =====
class RoomAvailability {
    constructor() {
        this.bookedRooms = JSON.parse(localStorage.getItem('bookedRooms')) || [];
    }

    checkAvailability(checkin, checkout, roomType = null, guestCount = 1) {
        const availableRooms = [];
        
        // Filter by room type if specified
        const typesToCheck = roomType ? [roomType] : Object.keys(ROOM_TYPES);
        
        typesToCheck.forEach(type => {
            const roomData = ROOM_TYPES[type];
            
            // Check if room can accommodate guests
            if (roomData.capacity >= guestCount) {
                // Simulate multiple rooms of each type (in real system, would come from database)
                const roomCount = this.getRoomCount(type);
                
                for (let i = 1; i <= roomCount; i++) {
                    const roomNumber = this.generateRoomNumber(type, i);
                    
                    if (this.isRoomAvailable(roomNumber, checkin, checkout)) {
                        availableRooms.push({
                            roomNumber: roomNumber,
                            type: type,
                            name: roomData.name,
                            capacity: roomData.capacity,
                            price: roomData.basePrice,
                            features: roomData.features,
                            image: roomData.image
                        });
                    }
                }
            }
        });
        
        return availableRooms;
    }

    isRoomAvailable(roomNumber, checkin, checkout) {
        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);
        
        return !this.bookedRooms.some(booking => {
            if (booking.roomNumber !== roomNumber) return false;
            
            const bookingCheckin = new Date(booking.checkin);
            const bookingCheckout = new Date(booking.checkout);
            
            // Check for date overlap
            return (checkinDate < bookingCheckout && checkoutDate > bookingCheckin);
        });
    }

    bookRoom(roomNumber, checkin, checkout, guestData) {
        const bookingCode = HotelUtils.generateBookingCode();
        const booking = {
            bookingCode: bookingCode,
            roomNumber: roomNumber,
            checkin: checkin,
            checkout: checkout,
            guestData: guestData,
            status: 'confirmed',
            createdAt: new Date().toISOString()
        };
        
        this.bookedRooms.push(booking);
        localStorage.setItem('bookedRooms', JSON.stringify(this.bookedRooms));
        
        return booking;
    }

    getRoomCount(type) {
        // Simulate different room counts for each type
        const counts = {
            standard: 20,
            deluxe: 15,
            suite: 8,
            presidential: 3
        };
        return counts[type] || 5;
    }

    generateRoomNumber(type, index) {
        const prefixes = {
            standard: '1',
            deluxe: '2', 
            suite: '3',
            presidential: '4'
        };
        
        const prefix = prefixes[type];
        const number = index.toString().padStart(2, '0');
        return `${prefix}${number}`;
    }
}

// ===== RESERVATION MANAGEMENT =====
class ReservationManager {
    constructor() {
        this.reservations = JSON.parse(localStorage.getItem('reservations')) || [];
    }

    createReservation(roomData, guestData, paymentMethod) {
        const reservation = {
            id: Date.now().toString(),
            bookingCode: HotelUtils.generateBookingCode(),
            room: roomData,
            guest: guestData,
            checkin: roomData.checkin,
            checkout: roomData.checkout,
            nights: HotelUtils.calculateDays(roomData.checkin, roomData.checkout),
            totalPrice: roomData.price * HotelUtils.calculateDays(roomData.checkin, roomData.checkout),
            paymentMethod: paymentMethod,
            status: 'confirmed',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };

        this.reservations.push(reservation);
        localStorage.setItem('reservations', JSON.stringify(this.reservations));

        return reservation;
    }

    getReservation(bookingCode) {
        return this.reservations.find(res => res.bookingCode === bookingCode);
    }

    updateReservationStatus(bookingCode, status) {
        const reservation = this.getReservation(bookingCode);
        if (reservation) {
            reservation.status = status;
            reservation.updatedAt = new Date().toISOString();
            localStorage.setItem('reservations', JSON.stringify(this.reservations));
        }
        return reservation;
    }

    getAllReservations() {
        return this.reservations;
    }

    getReservationsByDate(date) {
        return this.reservations.filter(res => {
            const checkinDate = new Date(res.checkin).toDateString();
            const checkoutDate = new Date(res.checkout).toDateString();
            const targetDate = new Date(date).toDateString();
            
            return checkinDate === targetDate || checkoutDate === targetDate;
        });
    }
}

// ===== GUEST MANAGEMENT =====
class GuestManager {
    constructor() {
        this.guests = JSON.parse(localStorage.getItem('guests')) || [];
    }

    addGuest(guestData) {
        const guest = {
            id: Date.now().toString(),
            ...guestData,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };

        this.guests.push(guest);
        localStorage.setItem('guests', JSON.stringify(this.guests));
        
        return guest;
    }

    findGuest(criteria) {
        return this.guests.find(guest => {
            return Object.keys(criteria).every(key => 
                guest[key] && guest[key].toLowerCase().includes(criteria[key].toLowerCase())
            );
        });
    }

    updateGuest(guestId, updateData) {
        const guestIndex = this.guests.findIndex(g => g.id === guestId);
        if (guestIndex !== -1) {
            this.guests[guestIndex] = {
                ...this.guests[guestIndex],
                ...updateData,
                updatedAt: new Date().toISOString()
            };
            localStorage.setItem('guests', JSON.stringify(this.guests));
            return this.guests[guestIndex];
        }
        return null;
    }

    getAllGuests() {
        return this.guests;
    }
}

// ===== NOTIFICATION SYSTEM =====
class NotificationSystem {
    static show(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification-popup`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${this.getIcon(type)} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    }

    static getIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
}

// ===== FORM VALIDATION =====
class FormValidator {
    static validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    static validatePhone(phone) {
        const phoneRegex = /^(\+62|62|0)[8][1-9][0-9]{6,9}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    static validateRequired(value) {
        return value && value.trim().length > 0;
    }

    static validateGuestForm(formData) {
        const errors = [];

        if (!this.validateRequired(formData.fullName)) {
            errors.push('Nama lengkap wajib diisi');
        }

        if (!this.validateRequired(formData.email)) {
            errors.push('Email wajib diisi');
        } else if (!this.validateEmail(formData.email)) {
            errors.push('Format email tidak valid');
        }

        if (!this.validateRequired(formData.phone)) {
            errors.push('Nomor HP wajib diisi');
        } else if (!this.validatePhone(formData.phone)) {
            errors.push('Format nomor HP tidak valid');
        }

        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
}

// ===== MAIN HOTEL SYSTEM CLASS =====
class HotelSystem {
    constructor() {
        this.roomAvailability = new RoomAvailability();
        this.reservationManager = new ReservationManager();
        this.guestManager = new GuestManager();
        this.notificationSystem = new NotificationSystem();
        this.formValidator = new FormValidator();
    }

    init() {
        console.log(`${HOTEL_CONFIG.name} System Initialized`);
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Common event listeners for all pages
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('book-room-btn')) {
                this.handleRoomBooking(e.target);
            }
        });
    }

    searchRooms(searchParams = {}) {
        const { checkinDate, checkoutDate, guests, roomType } = searchParams;
        
        // Show loading indicator
        this.showLoadingIndicator();
        
        // Simulate API delay
        setTimeout(() => {
            try {
                const availableRooms = this.roomAvailability.checkAvailability(
                    checkinDate, 
                    checkoutDate, 
                    roomType || null, 
                    parseInt(guests) || 1
                );
                
                this.displaySearchResults(availableRooms, searchParams);
                
            } catch (error) {
                console.error('Error searching rooms:', error);
                this.notificationSystem.show('Terjadi kesalahan saat mencari kamar', 'error');
                this.hideLoadingIndicator();
            }
        }, 1000);
    }

    displaySearchResults(rooms, searchParams) {
        this.hideLoadingIndicator();
        
        const container = document.getElementById('availableRoomsContainer');
        const noResultsMsg = document.getElementById('noResultsMessage');
        
        if (rooms.length === 0) {
            // Show no results message
            container.innerHTML = '';
            noResultsMsg.classList.remove('d-none');
            return;
        }
        
        // Hide no results message
        noResultsMsg.classList.add('d-none');
        
        // Generate room cards
        let roomsHTML = '';
        rooms.forEach(room => {
            roomsHTML += this.generateRoomCard(room, searchParams);
        });
        
        container.innerHTML = roomsHTML;
    }

    generateRoomCard(room, searchParams) {
        const nights = this.calculateNights(searchParams.checkinDate, searchParams.checkoutDate);
        const totalPrice = room.price * nights;
        
        return `
            <div class="col-md-6 col-lg-4 mb-4 room-card" data-room-number="${room.roomNumber}">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                    <div class="position-relative">
                        <img src="${room.image}" class="card-img-top room-image" 
                             style="height: 250px; object-fit: cover; border-radius: 15px 15px 0 0;" 
                             alt="${room.name}" onerror="this.src='img/hero-img-1.png'">
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge room-type-badge" 
                                  style="background: var(--luxury-gold); color: var(--luxury-black); font-weight: 600;">
                                ${room.type.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <h5 class="card-title room-name" style="color: var(--luxury-black); font-family: 'Playfair Display', serif;">
                            ${room.name}
                        </h5>
                        <p class="text-muted mb-2">Kamar ${room.roomNumber}</p>
                        <div class="room-details mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-users me-2" style="color: var(--luxury-gold);"></i>
                                <span>Maksimal ${room.capacity} tamu</span>
                            </div>
                            ${room.features.slice(0, 3).map(feature => `
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-check me-2" style="color: var(--luxury-gold); font-size: 0.8rem;"></i>
                                    <small>${feature}</small>
                                </div>
                            `).join('')}
                        </div>
                        <div class="pricing-info mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Harga per malam:</span>
                                <span class="fw-bold">${HotelUtils.formatCurrency(room.price)}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>${nights} malam:</span>
                                <span class="h5 text-primary" style="color: var(--luxury-gold) !important;">
                                    ${HotelUtils.formatCurrency(totalPrice)}
                                </span>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn book-room-btn" 
                                    style="background: var(--luxury-gold); color: var(--luxury-black); border: none; border-radius: 10px; font-weight: 600;"
                                    data-room='${JSON.stringify({...room, searchParams})}'>
                                <i class="fas fa-calendar-plus me-2"></i>Pesan Sekarang
                            </button>
                            <a href="room-detail.html?room=${room.roomNumber}" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-info-circle me-2"></i>Detail Kamar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    calculateNights(checkin, checkout) {
        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);
        const timeDiff = checkoutDate.getTime() - checkinDate.getTime();
        return Math.ceil(timeDiff / (1000 * 3600 * 24));
    }

    handleRoomBooking(button) {
        try {
            const roomData = JSON.parse(button.getAttribute('data-room'));
            
            // Store booking data in sessionStorage for the reservation form
            sessionStorage.setItem('selectedRoom', JSON.stringify(roomData));
            
            // Redirect to reservation form
            window.location.href = 'reservation-form.html';
            
        } catch (error) {
            console.error('Error handling room booking:', error);
            this.notificationSystem.show('Terjadi kesalahan saat memproses pemesanan', 'error');
        }
    }

    showLoadingIndicator() {
        const container = document.getElementById('availableRoomsContainer');
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        container.innerHTML = '';
        loadingIndicator.classList.remove('d-none');
    }

    hideLoadingIndicator() {
        const loadingIndicator = document.getElementById('loadingIndicator');
        loadingIndicator.classList.add('d-none');
    }
}

// ===== INITIALIZE SYSTEM =====
window.HotelSystem = new HotelSystem();

// ===== EXPORT FOR MODULE USAGE =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        HotelSystem: HotelSystem,
        RoomAvailability,
        ReservationManager,
        GuestManager,
        NotificationSystem,
        FormValidator,
        HOTEL_CONFIG,
        ROOM_TYPES
    };
}

// ===== CSS ANIMATIONS =====
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .notification-popup {
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        border: none;
        border-radius: 10px;
    }
`;
document.head.appendChild(style);
