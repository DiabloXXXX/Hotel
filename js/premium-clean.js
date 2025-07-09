/**
 * Premium Clean JavaScript - Hotel Senang Hati
 * Enhanced interactivity for navigation, booking, and hero sections
 */

(function() {
    'use strict';

    // DOM Content Loaded Event
    document.addEventListener('DOMContentLoaded', function() {
        
        // Initialize all components
        initSpinner();
        initStickyBooking();
        initBookingForm();
        initDateValidation();
        initMobileNavigation();
        
        console.log('Premium Clean JS initialized');
    });

    /**
     * Initialize and hide spinner
     */
    function initSpinner() {
        const spinner = document.getElementById('spinner');
        if (spinner) {
            // Hide spinner after page load
            setTimeout(() => {
                spinner.classList.remove('show');
                spinner.style.display = 'none';
            }, 1000);
        }
    }

    /**
     * Initialize sticky booking bar functionality - DISABLED
     * Booking form is now integrated in hero section
     */
    function initStickyBooking() {
        // Sticky booking functionality disabled - booking form now in hero
        console.log('Sticky booking disabled - using hero form');
    }

    // --- Micro-interaction tombol search ---
    const bookingForm = document.getElementById('quickBookingForm');
    if (bookingForm) {
      const searchBtn = bookingForm.querySelector('.search-btn');
      bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (searchBtn) {
          searchBtn.classList.add('loading');
          searchBtn.innerHTML = '<span>CHECKING...</span><span class="spinner"></span>';
        }
        setTimeout(() => {
          if (searchBtn) {
            searchBtn.classList.remove('loading');
            searchBtn.innerHTML = '<span>CHECK AVAILABILITY</span>';
          }
          // Build search parameters
          const checkIn = document.getElementById('checkIn').value;
          const checkOut = document.getElementById('checkOut').value;
          const guests = document.getElementById('guestCount').value;
          const roomType = document.getElementById('roomType').value;
          const searchParams = new URLSearchParams({
              checkin: checkIn,
              checkout: checkOut,
              guests: guests,
              room: roomType
          });
          // Redirect to room detail page with all params
          window.location.href = `room-detail.html?${searchParams.toString()}`;
        }, 1200);
      });
      // Validasi instan tanggal
      const checkIn = document.getElementById('checkIn');
      const checkOut = document.getElementById('checkOut');
      [checkIn, checkOut].forEach(input => {
        if (input) {
          input.addEventListener('input', function() {
            if (!checkIn.value || !checkOut.value) {
              input.style.borderColor = '#e74c3c';
              input.style.background = '#fff0f0';
            } else {
              input.style.borderColor = '';
              input.style.background = '';
            }
          });
        }
      });
      // Dropdown guests animasi
      const guestSelect = document.getElementById('guestCount');
      if (guestSelect) {
        guestSelect.addEventListener('focus', function() {
          guestSelect.style.boxShadow = '0 0 0 4px rgba(212,175,55,0.13)';
        });
        guestSelect.addEventListener('blur', function() {
          guestSelect.style.boxShadow = '';
        });
      }
    }

    // --- Overlay gambar kamar/fasilitas ---
    document.querySelectorAll('.room-img, .amenity-img').forEach(imgBox => {
      if (!imgBox.querySelector('.img-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'img-overlay';
        overlay.innerHTML = '<i class="fas fa-eye me-2"></i>Lihat Detail';
        imgBox.appendChild(overlay);
      }
    });

    // --- Navbar sticky interaktif & scroll spy ---
    function handleNavbarScroll() {
      const navbar = document.querySelector('.premium-navbar');
      if (!navbar) return;
      if (window.scrollY > 40) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
    window.addEventListener('scroll', handleNavbarScroll);
    handleNavbarScroll();

    // Scroll spy
    const sections = document.querySelectorAll('section[id], div[id]');
    const navLinks = document.querySelectorAll('.premium-nav-link');
    function scrollSpy() {
      let scrollPos = window.scrollY + 120;
      sections.forEach(sec => {
        if (sec.id && sec.offsetTop <= scrollPos && (sec.offsetTop + sec.offsetHeight) > scrollPos) {
          navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + sec.id) {
              link.classList.add('active');
            }
          });
        }
      });
    }
    window.addEventListener('scroll', scrollSpy);
    scrollSpy();

    // Smooth scroll nav
    navLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href && href.startsWith('#')) {
          e.preventDefault();
          const target = document.querySelector(href);
          if (target) {
            window.scrollTo({
              top: target.offsetTop - 110,
              behavior: 'smooth'
            });
          }
        }
      });
    });

    // Smooth scroll for anchor links
    $(document).on('click', '[data-scroll]', function(e) {
      var target = $(this).attr('href');
      if (target && target.startsWith('#') && $(target).length) {
        e.preventDefault();
        var offset = $(target).offset().top - 80; // adjust for sticky navbar height
        $('html, body').animate({ scrollTop: offset }, 700, 'easeInOutExpo');
        // Close mobile menu if open
        if($('.navbar-collapse').hasClass('show')) {
          $('.navbar-collapse').collapse('hide');
        }
      }
    });

    // Scroll spy for active nav link
    function updateActiveNav() {
      var scrollPos = $(window).scrollTop();
      var sections = ['#rooms', '#restaurant', '#events', '#facilities'];
      var found = false;
      for (var i = 0; i < sections.length; i++) {
        var section = $(sections[i]);
        if (section.length && scrollPos + 120 >= section.offset().top) {
          $('.premium-nav-link').removeClass('active');
          $('.premium-nav-link[href="' + sections[i] + '"]').addClass('active');
          found = true;
        }
      }
      if (!found) {
        $('.premium-nav-link').removeClass('active');
      }
    }
    $(window).on('scroll', updateActiveNav);
    $(document).ready(updateActiveNav);

    // Sticky navbar background transition
    $(window).on('scroll', function() {
      if ($(window).scrollTop() > 40) {
        $('.premium-navbar').addClass('scrolled');
      } else {
        $('.premium-navbar').removeClass('scrolled');
      }
    });

    // Show/hide booking form with smooth transition
    $(document).on('click', '#showBookingFormBtn', function() {
      var $formSection = $('#quickBookingForm');
      if ($formSection.is(':visible')) return;
      $formSection.slideDown(400).css('display', 'block');
      $('html, body').animate({ scrollTop: $formSection.offset().top - 80 }, 600, 'easeInOutExpo');
    });

    // Collapsible booking form on mobile
    function handleBookingFormCollapse() {
      if (window.innerWidth < 992) {
        $('#quickBookingForm').addClass('collapsible-booking');
      } else {
        $('#quickBookingForm').removeClass('collapsible-booking').show();
      }
    }
    $(window).on('resize', handleBookingFormCollapse);
    $(document).ready(handleBookingFormCollapse);

    // Booking form validation
    $(document).on('submit', '#quickBookingFormForm', function(e) {
      e.preventDefault();
      var checkIn = $('#checkIn').val();
      var checkOut = $('#checkOut').val();
      var guests = $('#guestCount').val();
      var errorMsg = '';
      if (!checkIn || !checkOut || !guests) {
        errorMsg = 'Please complete all required fields.';
      } else if (checkIn >= checkOut) {
        errorMsg = 'Check-in date must be before check-out date.';
      }
      if (errorMsg) {
        $('#bookingFormError').text(errorMsg).fadeIn();
        setTimeout(function(){ $('#bookingFormError').fadeOut(); }, 3000);
        return false;
      }
      // Success: proceed (simulate loading)
      $('#bookingFormError').hide();
      $('.search-btn').prop('disabled', true).text('Checking...');
      setTimeout(function(){
        $('.search-btn').prop('disabled', false).text('CHECK AVAILABILITY');
        // alert('Booking search submitted!'); // Dihilangkan, diganti modal pop-up di index.html
      }, 1200);
    });

    // --- Reservation Form AJAX Submission ---
    document.addEventListener('DOMContentLoaded', function() {
      const reservationForm = document.getElementById('reservationForm');
      if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(reservationForm);
          fetch('api/index.php?action=reserve', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if(data.success) {
              alert('Reservasi berhasil!');
              window.location.href = 'booking-confirmation.html?kode=' + data.kode_pesanan;
            } else {
              alert('Gagal reservasi: ' + (data.message || 'Terjadi kesalahan'));
            }
          })
          .catch(() => alert('Gagal menghubungi server.'));
        });
      }
    });

    /**
     * Show alert message
     */
    function showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alert.style.cssText = `
            top: 100px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: var(--shadow-luxury);
        `;
        
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add to body
        document.body.appendChild(alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    /**
     * Smooth scroll to section
     */
    function smoothScrollTo(targetId) {
        const target = document.querySelector(targetId);
        if (target) {
            const navbar = document.querySelector('.premium-navbar');
            const bookingSection = document.querySelector('.booking-search-section');
            const offset = (navbar?.offsetHeight || 0) + (bookingSection?.offsetHeight || 0) + 20;
            
            const targetPosition = target.offsetTop - offset;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    }

    // Global function for navigation
    window.navigateToSection = smoothScrollTo;

    /**
     * Initialize intersection observer for animations
     */
    function initAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('luxury-fade-in');
                }
            });
        }, observerOptions);
        
        // Observe elements that should animate
        const animateElements = document.querySelectorAll('.room-item, .service-item, .facility-item');
        animateElements.forEach(el => observer.observe(el));
    }
    
    // Initialize animations when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAnimations);
    } else {
        initAnimations();
    }

    // Smooth scroll for nav links
    document.querySelectorAll('.premium-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - (document.querySelector('.premium-navbar').offsetHeight + document.querySelector('.booking-search-section').offsetHeight),
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Fade-in animation for sections
    function fadeInSections() {
        const sections = document.querySelectorAll('.section-fade');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        sections.forEach(section => observer.observe(section));
    }
    fadeInSections();

    // Room detail modal logic
    var roomData = {
      standard: {
        name: 'Standard Room',
        desc: 'Kamar nyaman dengan fasilitas modern, tempat tidur queen size, pemandangan kota, AC, TV, dan kamar mandi pribadi.',
        images: ['Standar-room.png', 'room-service.png', 'parking-valet.png'],
        facilities: [
          {icon: 'fa-bed', label: 'Queen Bed'},
          {icon: 'fa-wifi', label: 'Free WiFi'},
          {icon: 'fa-bath', label: 'Private Bathroom'},
          {icon: 'fa-tv', label: 'TV'},
          {icon: 'fa-snowflake', label: 'AC'}
        ]
      },
      deluxe: {
        name: 'Deluxe Room',
        desc: 'Kamar luas dengan dekorasi elegan, tempat tidur king size, balkon pribadi, minibar, dan view kota.',
        images: ['Deluxe-room.png', 'rooftop-hotel.png', 'dining-area.png'],
        facilities: [
          {icon: 'fa-star', label: 'Terpopuler'},
          {icon: 'fa-umbrella-beach', label: 'Balkon'},
          {icon: 'fa-bed', label: 'King Bed'},
          {icon: 'fa-glass-martini', label: 'Minibar'},
          {icon: 'fa-city', label: 'City View'}
        ]
      },
      suite: {
        name: 'Executive Suite',
        desc: 'Suite mewah dengan ruang tamu terpisah, jacuzzi, layanan butler 24 jam, dan fasilitas premium.',
        images: ['Executive-room.png', 'concierge-service.png', 'spa-facility.png'],
        facilities: [
          {icon: 'fa-hot-tub', label: 'Jacuzzi'},
          {icon: 'fa-couch', label: 'Living Room'},
          {icon: 'fa-user-tie', label: 'Butler Service'},
          {icon: 'fa-spa', label: 'Spa Access'},
          {icon: 'fa-tv', label: 'Smart TV'}
        ]
      }
    };

    // Update existing product modal with room data
    function updateProductModal(roomKey) {
      var data = roomData[roomKey];
      if (!data) return;
      
      // Update modal title
      $('#productDetailModalLabel').html('<i class="fas fa-bed me-2"></i>' + data.name);
      
      // Update carousel images
      var carouselInner = $('#roomCarousel .carousel-inner');
      var imagesHtml = data.images.map(function(img, i) {
        return '<div class="carousel-item'+(i===0?' active':'')+'"><img src="img/'+img+'" class="d-block w-100" alt="'+data.name+' image '+(i+1)+'" style="height: 350px; object-fit: cover;" /></div>';
      }).join('');
      carouselInner.html(imagesHtml);
      
      // Update room description
      var descriptionElement = $('.modal-body .text-muted');
      if (descriptionElement.length) {
        descriptionElement.text(data.desc + ' dengan fasilitas terbaik untuk kenyamanan Anda.');
      }
      
      // Update room features with new single column layout
      var featureContainer = $('.modal-body .col-12 .d-flex.flex-column.gap-2');
      if (featureContainer.length) {
        var featuresHtml = data.facilities.map(function(fac) {
          return '<span class="d-flex align-items-center p-2 room-feature-item" style="font-size: 0.9rem; background: rgba(212, 175, 55, 0.05); border-radius: 8px; transition: all 0.3s ease;"><i class="fas '+fac.icon+' me-3" style="color: var(--primary-gold); width: 16px; font-size: 12px;"></i><span>'+fac.label+'</span></span>';
        }).join('');
        featureContainer.html(featuresHtml);
      }
      
      // Update pricing based on room type
      var pricing = {
        'standard': 'Rp 750.000',
        'deluxe': 'Rp 1.200.000', 
        'suite': 'Rp 2.500.000'
      };
      var priceElement = $('.modal-body span[style*="font-size: 1.3rem"]');
      if (priceElement.length && pricing[roomKey]) {
        priceElement.text(pricing[roomKey]);
      }
      
      // Update modal button to redirect to room-detail page
      var modalButton = $('#modalPesan');
      if (modalButton.length) {
        modalButton.off('click').on('click', function() {
          $('#productDetailModal').modal('hide');
          setTimeout(function() {
            window.location.href = 'room-detail.html?room=' + roomKey;
          }, 300);
        });
      }
    }

    // Modal order button now directly uses WhatsApp onclick, no need for separate handler
    // Legacy handler commented out:
    // $(document).on('click', '#orderNowBtn', function() {
    //   var modalTitle = $('#productDetailModalLabel').text();
    //   var roomType = 'deluxe'; // default
    //   
    //   if (modalTitle.includes('Standard')) roomType = 'standard';
    //   else if (modalTitle.includes('Deluxe')) roomType = 'deluxe';
    //   else if (modalTitle.includes('Executive') || modalTitle.includes('Suite')) roomType = 'suite';
    //   
    //   $('#productDetailModal').modal('hide');
    //   setTimeout(function() {
    //     window.location.href = 'reservation-form.html?room=' + roomType;
    //   }, 300);
    // });

    // Listen for modal show event to ensure proper update
    $('#productDetailModal').on('show.bs.modal', function (e) {
      var button = $(e.relatedTarget); // Button that triggered the modal
      var roomType = button.data('room'); // Extract room type from data-room attribute
      if (roomType) {
        updateProductModal(roomType);
      }
    });

    // Legacy buildRoomModal function - commented out, replaced by updateProductModal
    /*
    function buildRoomModal(roomKey) {
      console.log('Building modal for room:', roomKey);
      var data = roomData[roomKey];
      if (!data) {
        console.log('No data found for room:', roomKey);
        return;
      }
      console.log('Room data:', data);
      var imagesHtml = data.images.map(function(img, i) {
        return '<div class="carousel-item'+(i===0?' active':'')+'"><img src="img/'+img+'" class="d-block w-100 rounded" alt="'+data.name+' image '+(i+1)+'"></div>';
      }).join('');
      var facilitiesHtml = data.facilities.map(function(fac) {
        return '<span class="badge facility-badge mx-1 mb-2" title="'+fac.label+'"><i class="fas '+fac.icon+' me-1"></i>'+fac.label+'</span>';
      }).join(' ');
      var modalHtml = `
        <div class="modal fade show" id="roomDetailModal" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header luxury-badge">
                <h5 class="modal-title text-white">${data.name}</h5>
                <button type="button" class="btn-close btn-close-white" id="closeRoomModal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div id="roomCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
                  <div class="carousel-inner">${imagesHtml}</div>
                  <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                  <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                </div>
                <p class="mb-3">${data.desc}</p>
                <div class="mb-3">${facilitiesHtml}</div>
                <button class="btn btn-luxury-primary btn-lg w-100">Order Now</button>
              </div>
            </div>
          </div>
        </div>`;
      console.log('Appending modal to body...');
      $(modalHtml).appendTo('body');
      console.log('Modal appended, adding modal-open class...');
      $('body').addClass('modal-open');
    }
    */

    $(document).on('click', '.room-detail-btn', function() {
      var room = $(this).data('room');
      updateProductModal(room);
    });

    // Close modal handlers - commented out because we use existing Bootstrap modal
    // $(document).on('click', '#closeRoomModal, #roomDetailModal', function(e) {
    //   if (e.target === this) {
    //     $('#roomDetailModal').remove();
    //     $('body').removeClass('modal-open');
    //   }
    // });
    // // Prevent modal close on inner click
    // $(document).on('click', '.modal-content', function(e) {
    //   e.stopPropagation();
    // });

    // HERO VIDEO SLIDER LOGIC
    (function() {
      const video1 = document.getElementById('heroVideo1');
      const video2 = document.getElementById('heroVideo2');
      const fallbackImg = document.getElementById('heroFallbackImg');
      if (!video1 || !video2 || !fallbackImg) return;

      let state = 0; // 0: video1, 1: video2, 2: img
      function showVideo1() {
        video1.style.opacity = 1;
        video2.style.opacity = 0;
        fallbackImg.style.opacity = 0;
        video1.currentTime = 0;
        video1.play();
        setTimeout(() => {
          state = 1;
          showVideo2();
        }, 8000); // 8 detik
      }
      function showVideo2() {
        video1.pause();
        video2.style.opacity = 1;
        video1.style.opacity = 0;
        fallbackImg.style.opacity = 0;
        video2.currentTime = 0;
        video2.play();
        setTimeout(() => {
          state = 2;
          showImage();
        }, 8000); // 8 detik
      }
      function showImage() {
        video1.pause();
        video2.pause();
        fallbackImg.style.opacity = 1;
        video1.style.opacity = 0;
        video2.style.opacity = 0;
        setTimeout(() => {
          state = 0;
          showVideo1();
        }, 8000); // 8 detik
      }
      // Start the slider
      showVideo1();
    })();

    // --- Hero Booking Form Handler ---
    const heroBookingForm = document.getElementById('heroBookingForm');
    if (heroBookingForm) {
      const heroSearchBtn = heroBookingForm.querySelector('.search-btn');
      heroBookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (heroSearchBtn) {
          heroSearchBtn.classList.add('loading');
          heroSearchBtn.innerHTML = '<span>CHECKING...</span><span class="spinner"></span>';
        }
        setTimeout(() => {
          if (heroSearchBtn) {
            heroSearchBtn.classList.remove('loading');
            heroSearchBtn.innerHTML = '<span>CHECK AVAILABILITY</span>';
          }
          // Build search parameters
          const checkIn = document.getElementById('heroCheckIn').value;
          const checkOut = document.getElementById('heroCheckOut').value;
          const guests = document.getElementById('heroGuestCount').value;
          const roomType = document.getElementById('heroRoomType').value;
          const searchParams = new URLSearchParams({
              checkin: checkIn,
              checkout: checkOut,
              guests: guests,
              room: roomType
          });
          // Redirect to room detail page with search parameters
          window.location.href = `room-detail.html?${searchParams.toString()}`;
        }, 1500);
      });
    }

})();
