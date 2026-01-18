document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('cookie-consent-banner');
    const GA_MEASUREMENT_ID = 'G-552HNY58WN'; 

    // Function to load Google Analytics
    function loadGoogleAnalytics() {
        // Create the script tag for the Google Analytics library
        const script = document.createElement('script');
        script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`;
        script.async = true;
        document.head.appendChild(script);

        // Initialize the DataLayer
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', GA_MEASUREMENT_ID, { 'anonymize_ip': true });

        console.log('Google Analytics loaded.');
    }

    // Check if user has already made a choice
    const consent = localStorage.getItem('cookieConsent');

    if (consent === 'accepted') {
        loadGoogleAnalytics();
    } else if (consent === 'declined') {
        // Do nothing
    } else {
        // Show banner if no choice has been made
        createBannerHTML();
    }

    function createBannerHTML() {
        const div = document.createElement('div');
        div.id = 'cookie-consent-banner';
        div.innerHTML = `
            <div class="cookie-content">
                <div class="cookie-text">
                    <h3><i class="fas fa-cookie-bite"></i> We value your privacy</h3>
                    <p>We use cookies to analyze website traffic and improve your experience. By clicking "Accept", you consent to the use of these cookies.</p>
                </div>
                <div class="cookie-buttons">
                    <button id="cookie-decline" class="cookie-btn decline">Decline</button>
                    <button id="cookie-accept" class="cookie-btn accept">Accept</button>
                </div>
            </div>
        `;
        document.body.appendChild(div);
        
        // Add event listeners
        document.getElementById('cookie-accept').addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'accepted');
            document.getElementById('cookie-consent-banner').style.display = 'none';
            loadGoogleAnalytics();
        });

        document.getElementById('cookie-decline').addEventListener('click', () => {
            localStorage.setItem('cookieConsent', 'declined');
            document.getElementById('cookie-consent-banner').style.display = 'none';
        });

        div.style.display = 'block';
    }
});