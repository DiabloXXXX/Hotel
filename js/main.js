(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);


    // Fixed Navbar
    $(window).scroll(function () {
        if ($(window).width() < 992) {
            if ($(this).scrollTop() > 55) {
                $('.fixed-top').addClass('shadow');
            } else {
                $('.fixed-top').removeClass('shadow');
            }
        } else {
            if ($(this).scrollTop() > 55) {
                $('.fixed-top').addClass('shadow').css('top', 0);
            } else {
                $('.fixed-top').removeClass('shadow').css('top', 0);
            }
        }
    });


    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > -300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });


    // Testimonial carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 2000,
        center: false,
        dots: true,
        loop: true,
        margin: 25,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 1
            },
            768: {
                items: 1
            },
            992: {
                items: 2
            },
            1200: {
                items: 2
            }
        }
    });


    // vegetable carousel
    $(".vegetable-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        center: false,
        dots: true,
        loop: true,
        margin: 25,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 1
            },
            768: {
                items: 2
            },
            992: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });


    // Modal Video
    $(document).ready(function () {
        var $videoSrc;
        $('.btn-play').click(function () {
            $videoSrc = $(this).data("src");
        });
        // console.log($videoSrc); // Debug line removed for production

        $('#videoModal').on('shown.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0");
        })

        $('#videoModal').on('hide.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc);
        })
    });



    // Product Quantity
    $('.quantity button').on('click', function () {
        var button = $(this);
        var oldValue = button.parent().parent().find('input').val();
        if (button.hasClass('btn-plus')) {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            if (oldValue > 0) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 0;
            }
        }
        button.parent().parent().find('input').val(newVal);
    });

})(jQuery);

// Fungsi Global untuk Modal dan Interaksi (Di luar jQuery wrapper)

// Fungsi untuk menampilkan modal detail produk
function showProductDetail(name, image, price, description, category) {
    document.getElementById('detailProductName').textContent = name;
    document.getElementById('detailProductImage').src = image;
    document.getElementById('detailProductPrice').textContent = price;
    document.getElementById('detailProductDescription').textContent = description;
    document.getElementById('detailProductCategory').textContent = category;
    
    // Set data untuk tombol add to cart di modal detail
    const addToCartBtn = document.getElementById('addToCartFromDetail');
    addToCartBtn.setAttribute('data-product-name', name);
    addToCartBtn.setAttribute('data-product-image', image);
    addToCartBtn.setAttribute('data-product-price', price);
    addToCartBtn.setAttribute('data-product-price-num', price.replace('Rp ', '').replace('.', ''));
    
    // Tampilkan modal
    const modal = new bootstrap.Modal(document.getElementById('productDetailModal'));
    modal.show();
}

// Fungsi untuk menampilkan modal konfirmasi pembelian
function showConfirmPurchase(name, image, price, priceNum) {
    document.getElementById('confirmProductName').textContent = name;
    document.getElementById('confirmProductImage').src = image;
    document.getElementById('confirmProductPrice').textContent = price;
    document.getElementById('quantityInput').value = '1';
    
    // Hitung total harga
    updateTotalPrice(priceNum);
    
    // Set data produk untuk konfirmasi
    const modal = document.getElementById('confirmPurchaseModal');
    modal.setAttribute('data-product-name', name);
    modal.setAttribute('data-product-image', image);
    modal.setAttribute('data-product-price', price);
    modal.setAttribute('data-product-price-num', priceNum);
    
    // Tampilkan modal
    const confirmModal = new bootstrap.Modal(modal);
    confirmModal.show();
}

// Fungsi untuk mengupdate total harga
function updateTotalPrice(priceNum) {
    const quantity = parseInt(document.getElementById('quantityInput').value);
    const total = priceNum * quantity;
    document.getElementById('confirmTotalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

// Fungsi untuk menambahkan produk ke local storage (simulasi keranjang)
function addToCart(productData) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Cek apakah produk sudah ada di keranjang
    const existingProductIndex = cart.findIndex(item => item.name === productData.name);
    
    if (existingProductIndex > -1) {
        // Jika sudah ada, tambahkan quantity
        cart[existingProductIndex].quantity += productData.quantity;
    } else {
        // Jika belum ada, tambahkan sebagai item baru
        cart.push(productData);
    }
    
    // Simpan ke localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Tampilkan modal sukses
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    successModal.show();
    
    // Update cart count
    updateCartCount();
}

// Fungsi untuk mengupdate jumlah item di keranjang
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    // Update cart badge jika ada
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.textContent = totalItems;
        cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
}

// Event listeners untuk modal dan interaksi
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi cart count
    updateCartCount();
    
    // Event listener untuk tombol add to cart
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const name = this.getAttribute('data-product-name');
            const image = this.getAttribute('data-product-image');
            const price = this.getAttribute('data-product-price');
            const priceNum = parseInt(this.getAttribute('data-product-price-num'));
            
            showConfirmPurchase(name, image, price, priceNum);
        });
    });
    
    // Event listener untuk tombol add to cart dari modal detail
    const addToCartFromDetailBtn = document.getElementById('addToCartFromDetail');
    if (addToCartFromDetailBtn) {
        addToCartFromDetailBtn.addEventListener('click', function() {
            const name = this.getAttribute('data-product-name');
            const image = this.getAttribute('data-product-image');
            const price = this.getAttribute('data-product-price');
            const priceNum = parseInt(this.getAttribute('data-product-price-num'));
            
            // Tutup modal detail dan buka modal konfirmasi
            bootstrap.Modal.getInstance(document.getElementById('productDetailModal')).hide();
            setTimeout(() => {
                showConfirmPurchase(name, image, price, priceNum);
            }, 300);
        });
    }
    
    // Event listener untuk tombol quantity di modal konfirmasi
    const increaseBtn = document.getElementById('increaseQuantity');
    const decreaseBtn = document.getElementById('decreaseQuantity');
    
    if (increaseBtn) {
        increaseBtn.addEventListener('click', function() {
            const input = document.getElementById('quantityInput');
            const currentValue = parseInt(input.value);
            input.value = currentValue + 1;
            
            const modal = document.getElementById('confirmPurchaseModal');
            const priceNum = parseInt(modal.getAttribute('data-product-price-num'));
            updateTotalPrice(priceNum);
        });
    }
    
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', function() {
            const input = document.getElementById('quantityInput');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                
                const modal = document.getElementById('confirmPurchaseModal');
                const priceNum = parseInt(modal.getAttribute('data-product-price-num'));
                updateTotalPrice(priceNum);
            }
        });
    }
    
    // Event listener untuk konfirmasi add to cart
    const addToCartConfirmBtn = document.getElementById('addToCartConfirm');
    if (addToCartConfirmBtn) {
        addToCartConfirmBtn.addEventListener('click', function() {
            const modal = document.getElementById('confirmPurchaseModal');
            const productData = {
                name: modal.getAttribute('data-product-name'),
                image: modal.getAttribute('data-product-image'),
                price: modal.getAttribute('data-product-price'),
                priceNum: parseInt(modal.getAttribute('data-product-price-num')),
                quantity: parseInt(document.getElementById('quantityInput').value)
            };
            
            // Tutup modal konfirmasi
            bootstrap.Modal.getInstance(modal).hide();
            
            // Tambahkan ke cart setelah delay singkat
            setTimeout(() => {
                addToCart(productData);
            }, 300);
        });
    }
});

