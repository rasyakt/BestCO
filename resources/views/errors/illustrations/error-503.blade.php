<!-- Ilustrasi 503: Pemeliharaan / alat baku -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="toolGrad503" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#a78bfa"/>
                <stop offset="100%" stop-color="#6d28d9"/>
            </linearGradient>
            <filter id="shadow503" x="-20%" y="-20%" width="140%" height="140%">
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

        <!-- Kotak alat -->
        <rect x="110" y="130" width="180" height="110" rx="12" fill="#f3e8ff" opacity="0.2" stroke="#a78bfa" stroke-width="2"/>
        <rect x="120" y="140" width="160" height="90" fill="none" stroke="#a78bfa" stroke-width="1" stroke-dasharray="8 8" opacity="0.5"/>

        <!-- Obeng -->
        <g transform="translate(180, 210)" filter="url(#shadow503)">
            <line x1="-30" y1="0" x2="30" y2="0" stroke="#a78bfa" stroke-width="5" stroke-linecap="round"/>
            <rect x="-6" y="-3" width="12" height="6" rx="2" fill="#a78bfa"/>
            <circle cx="33" cy="0" r="6" fill="#a78bfa" stroke="#6d28d9" stroke-width="2"/>
        </g>

        <!-- Sekrup -->
        <g transform="translate(240, 190)" filter="url(#shadow503)">
            <rect x="-4" y="-20" width="8" height="40" fill="#a78bfa" rx="2"/>
            <circle cx="0" cy="0" r="6" fill="#a78bfa" stroke="#6d28d9" stroke-width="2"/>
            <line x1="0" y1="-6" x2="0" y2="6" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"/>
            <line x1="-3" y1="0" x2="3" y2="0" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"/>
            <animateTransform attributeName="transform" type="rotate" values="0 240 190;15 240 190;-15 240 190;0 240 190" dur="2s" repeatCount="indefinite"/>
        </g -->

        <!-- Gigi gulungan -->
        <g transform="translate(290, 230)" opacity="0.9">
            <circle cx="0" cy="0" r="14" fill="none" stroke="#a78bfa" stroke-width="4"/>
            <path d="M-10 0 Q0 -6 10 0 M-6 -10 Q6 -10 6 10 M-6 10 Q6 10 6 -10" stroke="#a78bfa" stroke-width="2" stroke-linecap="round"/>
            <circle cx="0" cy="0" r="4" fill="#a78bfa"/>
            <animateTransform attributeName="transform" type="rotate" values="0 0 0;360 0 0" dur="3s" repeatCount="indefinite"/>
        </g>

        <!-- Ikon pendingin / semakin -->
        <g transform="translate(150, 180)" opacity="0.9">
            <rect x="-6" y="-12" width="12" height="24" fill="#a78bfa" rx="2"/>
            <path d="M-6 -12 L6 -12 L0 0 Z" fill="#6d28d9"/>
        </g>

        <!-- Titik di sekitar -->
        <circle cx="130" cy="100" r="3" fill="#a78bfa" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="270" cy="120" r="3" fill="#a78bfa" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2.4s" repeatCount="indefinite"/>
        </circle>
    </svg>
</div>
