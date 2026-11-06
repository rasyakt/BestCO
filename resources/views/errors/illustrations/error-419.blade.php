<!-- Ilustrasi 419: Jam jam / waktu kadaluarsa -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="clockGrad419" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#fb923c"/>
                <stop offset="100%" stop-color="#ea580c"/>
            </linearGradient>
            <filter id="shadow419" x="-20%" y="-20%" width="140%" height="140%">
                <feGaussianBlur in="SourceAlpha" stdDeviation="8"/>
                <feOffset dx="0" dy="10" result="offsetblur"/>
                <feComponentTransfer>
                    <feFuncA type="linear" slope="0.25"/>
                </feComponentTransfer>
                <feMerge>
                    <feMergeNode/>
                    <feMergeNode in="SourceGraphic"/>
                </feMerge>
            </filter>
        </defs>

        <!-- Jam dinding -->
        <circle cx="200" cy="150" r="95" fill="#fff7ed" opacity="0.2" stroke="#fb923c" stroke-width="2" stroke-dasharray="8 8"/>
        <circle cx="200" cy="150" r="85" fill="none" stroke="#fb923c" stroke-width="3" opacity="0.6"/>

        <!-- Tanda jam -->
        <g transform="translate(200, 150)">
            <circle r="4" fill="#fb923c"/>
            <g transform="rotate(30)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(60)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(90)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(120)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(150)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(180)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(210)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(240)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(270)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(300)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
            <g transform="rotate(330)"><circle cx="0" cy="-80" r="3" fill="#fb923c" opacity="0.6"/></g>
        </g>

        <!-- Jarum jam (berputar cepat = waktu habis) -->
        <g transform="translate(200, 150)" filter="url(#shadow419)">
            <line x1="0" y1="0" x2="0" y2="-50" stroke="#fb923c" stroke-width="5" stroke-linecap="round"/>
            <line x1="0" y1="0" x2="35" y2="0" stroke="#ea580c" stroke-width="3" stroke-linecap="round"/>
            <circle cx="0" cy="0" r="6" fill="#ea580c"/>

            <!-- Jarum detik berputar -->
            <animateTransform attributeName="transform" type="rotate" values="0 0 0;360 0 0" dur="1.5s" repeatCount="indefinite"/>
        </g>

        <!-- Ikon kadaluarsa -->
        <g transform="translate(200, 240)" opacity="0.9">
            <path d="M-10 0 L10 0 M0 -5 L0 5" stroke="#ef4444" stroke-width="3" stroke-linecap="round"/>
            <animateTransform attributeName="transform" type="translate" values="0 0;-4 0;4 0;0 0" dur="1s" repeatCount="indefinite"/>
        </g>

        <!-- Titik di sekitar -->
        <circle cx="130" cy="90" r="3" fill="#fb923c" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="270" cy="210" r="3" fill="#fb923c" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2.4s" repeatCount="indefinite"/>
        </circle>
    </svg>
</div>
