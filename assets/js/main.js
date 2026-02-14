/**
 * HStore - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Copy to clipboard functionality
    document.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var text = this.getAttribute('data-copy') || this.previousElementSibling.textContent;
            navigator.clipboard.writeText(text.trim()).then(function() {
                showToast('Copied to clipboard!', 'success');
            }).catch(function() {
                showToast('Failed to copy', 'error');
            });
        });
    });

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // Image preview on file input
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
        input.addEventListener('change', function() {
            var previewId = this.getAttribute('data-preview');
            var preview = document.getElementById(previewId);
            if (preview && this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Payment timer countdown
    var timerElements = document.querySelectorAll('.payment-timer[data-expires]');
    timerElements.forEach(function(timer) {
        var expiresAt = new Date(timer.getAttribute('data-expires')).getTime();
        
        var countdown = setInterval(function() {
            var now = new Date().getTime();
            var distance = expiresAt - now;
            
            if (distance < 0) {
                clearInterval(countdown);
                timer.textContent = 'EXPIRED';
                timer.classList.add('expired');
                return;
            }
            
            var hours = Math.floor(distance / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            timer.textContent = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
        }, 1000);
    });

    // Payment status checker
    var paymentChecker = document.querySelector('[data-payment-check]');
    if (paymentChecker) {
        var paymentId = paymentChecker.getAttribute('data-payment-check');
        var checkInterval = setInterval(function() {
            fetch(BASE_URL + '/api/check-payment.php?id=' + paymentId)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.confirmed) {
                        clearInterval(checkInterval);
                        showToast('Payment confirmed!', 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    }
                })
                .catch(function(error) {
                    console.error('Payment check error:', error);
                });
        }, 10000); // Check every 10 seconds
    }

    // Star rating input
    document.querySelectorAll('.star-rating-input').forEach(function(container) {
        var input = container.querySelector('input[type="hidden"]');
        var stars = container.querySelectorAll('.star');
        
        stars.forEach(function(star, index) {
            star.addEventListener('click', function() {
                var value = index + 1;
                input.value = value;
                
                stars.forEach(function(s, i) {
                    if (i < value) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                var value = index + 1;
                stars.forEach(function(s, i) {
                    if (i < value) {
                        s.classList.add('text-warning');
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                stars.forEach(function(s) {
                    s.classList.remove('text-warning');
                });
            });
        });
    });

    // Quantity input controls
    document.querySelectorAll('.quantity-control').forEach(function(control) {
        var input = control.querySelector('input');
        var minusBtn = control.querySelector('.qty-minus');
        var plusBtn = control.querySelector('.qty-plus');
        var min = parseInt(input.getAttribute('min')) || 1;
        var max = parseInt(input.getAttribute('max')) || 999;
        
        minusBtn.addEventListener('click', function() {
            var value = parseInt(input.value) - 1;
            if (value >= min) {
                input.value = value;
                input.dispatchEvent(new Event('change'));
            }
        });
        
        plusBtn.addEventListener('click', function() {
            var value = parseInt(input.value) + 1;
            if (value <= max) {
                input.value = value;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    // Search autocomplete (basic implementation)
    var searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        var timeout = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            var query = this.value;
            
            if (query.length < 2) return;
            
            timeout = setTimeout(function() {
                // Could implement autocomplete here
            }, 300);
        });
    }

    // Lazy load images
    if ('IntersectionObserver' in window) {
        var lazyImages = document.querySelectorAll('img[data-src]');
        var imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    }
});

// Toast notification function
function showToast(message, type) {
    type = type || 'info';
    var bgClass = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info'
    }[type] || 'bg-info';
    
    var toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    var toastEl = document.createElement('div');
    toastEl.className = 'toast ' + bgClass + ' text-white';
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = 
        '<div class="d-flex">' +
            '<div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
        '</div>';
    
    toastContainer.appendChild(toastEl);
    var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

// Format currency
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2) + ' USDT';
}

// AJAX form submission helper
function submitForm(form, callback) {
    var formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method || 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (callback) callback(data);
    })
    .catch(function(error) {
        console.error('Form submission error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

// Global BASE_URL (set in header)
var BASE_URL = window.BASE_URL || '';
