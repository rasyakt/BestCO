<!-- Ilustrasi 401: Kunci terbuka + profil -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="lockGrad401" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#3b82f6"/>
                <stop offset="100%" stop-color="#2563eb"/>
            </linearGradient>
            <filter id="shadow401" x="-20%" y="-20%" width="140%" height="140%">
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

        <!-- Lingkaran akses -->
        <circle cx="200" cy="150" r="110" fill="#dbeafe" opacity="0.2"/>
        <circle cx="200" cy="150" r="90" fill="none" stroke="#3b82f6" stroke-width="2" stroke-dasharray="6 6" opacity="0.5">
            <animate attributeName="stroke-dashoffset" from="0" to="80" dur="3s" repeatCount="indefinite"/>
        </circle>

        <!-- Kunci terbuka -->
        <g transform="translate(200, 150)" filter="url(#shadow401)">
            <path d="M-25 -20 L-5 0 L-25 20 Z" fill="url(#lockGrad401)" stroke="#1d4ed8" stroke-width="2"/>
            <rect x="-10" y="-8" width="20" height="16" rx="2" fill="url(#lockGrad401)" stroke="#1d4ed8" stroke-width="2"/>
            <circle cx="-20" cy="8" r="5" fill="#ffffff" stroke="#1d4ed8" stroke-width="2"/>
            <circle cx="-20" cy="8" r="2" fill="#1d4ed8"/>

            <!-- Animasi kunci berputar terbuka -->
            <animateTransform attributeName="transform" type="rotate" values="0 200 150;-15 200 150;0 200 150" dur="2s" repeatCount="indefinite"/>
        </g>

        <!-- Ikon profil / pengguna -->
        <g transform="translate(200, 120)">
            <circle cx="0" cy="-20" r="18" fill="#cbd5e1"/>
            <path d="M-22 5 Q0 25 22 5" fill="none" stroke="#cbd5e1" stroke-width="4" stroke-linecap="round"/>
        </g>

        <!-- Titik akses -->
        <circle cx="140" cy="100" r="3" fill="#3b82f6" opacity="0.8">
            <animate attributeName="opacity" values="0.4;1;0.4" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="260" cy="200" r="3" fill="#3b82f6" opacity="0.8">
            <animate attributeName="opacity" values="0.4;1;0.4" dur="2.6s" repeatCount="indefinite"/>
        </circle>
    </svg>
</div>
