// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            menuToggle.innerHTML = navMenu.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
        
        // Close menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                if (menuToggle) {
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
        });
    }
    
    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const themeIcon = themeToggle.querySelector('i');
        
        // Check for saved theme preference or use preferred color scheme
        const savedTheme = localStorage.getItem('theme') || 
                         (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }
        
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            
            if (document.body.classList.contains('dark-mode')) {
                themeIcon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'light');
            }
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Set active nav link based on current page
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref && linkHref.includes(currentPage)) {
            link.classList.add('active');
        }
    });

    // Toggle reply form visibility
    $('.reply-btn').on('click', function() {
        $(this).closest('.message-card').find('.reply-form-container').slideToggle();
    });

    // Handle email sending (frontend part)
    $('.reply-email-form').on('submit', function(event) {
        event.preventDefault();
        var form = $(this);
        var messageId = form.data('message-id');
        var toEmail = form.find('.reply-to').val();
        var fromEmail = form.find('.reply-from').val();
        var ccEmail = form.find('.reply-cc').val();
        var subject = form.find('.reply-subject').val();
        var messageBody = form.find('.reply-message').val();
        var replyBtn = form.closest('.message-card').find('.reply-btn');

        // For now, just log the email details and update UI
        console.log('--- Sending Email ---');
        console.log('To:', toEmail);
        console.log('From:', fromEmail);
        console.log('CC:', ccEmail);
        console.log('Subject:', subject);
        console.log('Message:', messageBody);
        console.log('---------------------');

        // In a real application, you would send this data to a backend PHP script
        // that uses a mail library (like PHPMailer) to send the email.
        // For demonstration, we\'ll just show an alert and mark as replied.

        alert('Email simulated to be sent! (Check console for details)');

        // Mark as replied in the database
        $.ajax({
            url: 'admin_update_reply_status.php',
            type: 'POST',
            data: {
                id: messageId,
                action: 'mark_replied'
            },
            success: function(response) {
                if (response === 'Success') {
                    // Update UI immediately
                    form.closest('.reply-form-container').slideUp();
                    form.closest('.message-card').addClass('replied');
                    replyBtn.html('<i class="fas fa-reply"></i> Replied');
                    // Optionally, reload to see sorting changes
                    location.reload();
                } else {
                    alert('Error updating reply status: ' + response);
                }
            },
            error: function() {
                alert('Error communicating with server to update reply status.');
            }
        });
    });
});