<!-- Ilustrasi 403: Kunci & pergubungan terpancung -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="keyGrad403" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#f59e0b"/>
                <stop offset="100%" stop-color="#d97706"/>
            </linearGradient>
            <filter id="shadow403" x="-20%" y="-20%" width="140%" height="140%">
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

        <!-- Lingkaran perlindungan -->
        <circle cx="200" cy="150" r="120" fill="#fef3c7" opacity="0.15" stroke="#f59e0b" stroke-width="1" stroke-dasharray="10 10"/>
        <circle cx="200" cy="150" r="100" fill="none" stroke="#f59e0b" stroke-width="2" stroke-dasharray="4 4" opacity="0.4">
            <animate attributeName="stroke-dashoffset" from="0" to="100" dur="4s" repeatCount="indefinite"/>
        </circle>

        <!-- Kunci -->
        <g transform="translate(200, 150)" filter="url(#shadow403)">
            <!-- Badan kunci -->
            <path d="M-30 -10 L-10 10 L-30 30 Z" fill="url(#keyGrad403)" stroke="#b45309" stroke-width="2"/>
            <rect x="-12" y="-6" width="24" height="20" rx="3" fill="url(#keyGrad403)" stroke="#b45309" stroke-width="2"/>
            <!-- Gigi kunci -->
            <path d="M12 4 L12 14 L6 14 L6 10" fill="#b45309"/>
            <!-- Lingkaran kunci -->
            <circle cx="-20" cy="10" r="6" fill="#ffffff" stroke="#b45309" stroke-width="2"/>
            <circle cx="-20" cy="10" r="2.5" fill="#b45309"/>

            <!-- Animasi getaran kunci (coba buka tapi gagal) -->
            <animateTransform attributeName="transform" type="rotate" values="-5 200 150;5 200 150;-5 200 150" dur="1.2s" repeatCount="indefinite"/>
        </g>

        <!-- Ikon "larang" / terpancung -->
        <g transform="translate(200, 150)">
            <circle cx="0" cy="0" r="70" fill="none" stroke="#ef4444" stroke-width="6" stroke-linecap="round"/>
            <line x1="-50" y1="-50" x2="50" y2="50" stroke="#ef4444" stroke-width="6" stroke-linecap="round"/>
        </g>

        <!-- Titik di sekitar -->
        <circle cx="140" cy="110" r="3" fill="#f59e0b" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="260" cy="190" r="3" fill="#f59e0b" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2.5s" repeatCount="indefinite"/>
        </circle>
    </svg>
</div>
