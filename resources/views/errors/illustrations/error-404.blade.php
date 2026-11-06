<!-- Ilustrasi 404: Peta dengan pin hilang + animasi -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="mapGrad404" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#3b82f6"/>
                <stop offset="100%" stop-color="#2563eb"/>
            </linearGradient>
            <linearGradient id="pinGrad404" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#fbbf24"/>
                <stop offset="100%" stop-color="#d97706"/>
            </linearGradient>
            <filter id="shadow404" x="-20%" y="-20%" width="140%" height="140%">
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

        <!-- Kontur pita abtract peta -->
        <path d="M40 260 Q 60 200 100 180 Q 140 160 180 190 Q 220 220 260 200 Q 300 180 340 210 Q 380 240 360 280 L 40 280 Z" fill="url(#mapGrad404)" opacity="0.15"/>

        <!-- Garis jalan peta -->
        <path d="M60 240 Q 100 220 150 210 Q 200 200 250 230 Q 300 250 340 240" fill="none" stroke="#3b82f6" stroke-width="3" stroke-dasharray="8 4" opacity="0.6"/>
        <path d="M100 180 Q 140 170 190 185 Q 240 200 290 190 Q 330 180 360 200" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-dasharray="6 6" opacity="0.5"/>

        <!-- Pin lokasi abstrata (hilang/404) -->
        <g transform="translate(190, 110)" filter="url(#shadow404)">
            <path d="M20 0 L0 30 L-20 0 Z" fill="#fbbf24" stroke="#d97706" stroke-width="2"/>
            <circle cx="0" cy="0" r="12" fill="#ffffff" stroke="#d97706" stroke-width="2"/>
            <circle cx="0" cy="0" r="5" fill="#d97706" animateTransform attributeName="transform" type="scale" values="1;1.3;1" dur="2s" repeatCount="indefinite"/>
        </g>

        <!-- Titik-titik lokasi kecil -->
        <circle cx="110" cy="200" r="4" fill="#3b82f6" opacity="0.8">
            <animate attributeName="opacity" values="0.4;1;0.4" dur="3s" repeatCount="indefinite"/>
        </circle>
        <circle cx="280" cy="230" r="4" fill="#3b82f6" opacity="0.8">
            <animate attributeName="opacity" values="0.4;1;0.4" dur="3.5s" repeatCount="indefinite"/>
        </circle>
        <circle cx="160" cy="250" r="3" fill="#2563eb" opacity="0.6">
            <animate attributeName="opacity" values="0.3;0.9;0.3" dur="2.8s" repeatCount="indefinite"/>
        </circle>

        <!-- Ikon "tidak ada" di atas pin -->
        <g transform="translate(190, 100)" opacity="0.9">
            <path d="M-8 -8 L8 8 M8 -8 L-8 8" stroke="#ef4444" stroke-width="3" stroke-linecap="round"/>
            <animateTransform attributeName="transform" type="rotate" values="0 0 0;10 0 0;-10 0 0;0 0 0" dur="1.5s" repeatCount="indefinite"/>
        </g>
    </svg>
</div>
