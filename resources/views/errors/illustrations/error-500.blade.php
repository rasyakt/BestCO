<!-- Ilustrasi 500: Server / awan lalu kabur -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="serverGrad500" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#f43f5e"/>
                <stop offset="100%" stop-color="#be123c"/>
            </linearGradient>
            <filter id="shadow500" x="-20%" y="-20%" width="140%" height="140%">
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

        <!-- Awan latar -->
        <path d="M60 140 Q 100 100 150 120 Q 200 100 240 140 Q 280 100 320 140 Q 340 160 300 180 Q 250 190 200 180 Q 150 190 100 160 Q 80 150 60 140 Z" fill="#fecaca" opacity="0.3"/>
        <path d="M70 135 Q 110 95 160 115 Q 210 95 260 135 Q 300 95 340 135 Q 360 155 320 175 Q 270 185 220 175 Q 170 185 110 155 Q 90 145 70 135 Z" fill="#fee2e2" opacity="0.5"/>

        <!-- Server rack -->
        <g transform="translate(200, 160)" filter="url(#shadow500)">
            <rect x="-50" y="0" width="100" height="80" rx="8" fill="#fecaca" stroke="#f43f5e" stroke-width="2"/>
            <rect x="-45" y="10" width="90" height="60" fill="#fee2e2" opacity="0.6"/>
            <!-- Indikator server -->
            <rect x="-40" y="20" width="4" height="4" fill="#f43f5e" rx="2">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="1s" repeatCount="indefinite"/>
            </rect>
            <rect x="-30" y="20" width="4" height="4" fill="#f43f5e" rx="2">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="1.3s" repeatCount="indefinite"/>
            </rect>
            <rect x="-20" y="20" width="4" height="4" fill="#f43f5e" rx="2">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="0.8s" repeatCount="indefinite"/>
            </rect>
            <rect x="-10" y="20" width="4" height="4" fill="#f43f5e" rx="2">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="1.1s" repeatCount="indefinite"/>
            </rect>
            <rect x="0" y="20" width="4" height="4" fill="#f43f5e" rx="2">
                <animate attributeName="opacity" values="0.3;1;0.3" dur="0.9s" repeatCount="indefinite"/>
            </rect>
        </g>

        <!-- Ikon crash / ledak -->
        <g transform="translate(200, 110)" opacity="0.9">
            <path d="M-12 -4 L-4 8 L4 -4 L12 8 Z" fill="#ef4444"/>
            <path d="M-8 -10 L8 -10 M0 -10 L0 2" stroke="#ef4444" stroke-width="3" stroke-linecap="round"/>
            <animateTransform attributeName="transform" type="scale" values="1;1.15;1" dur="1.2s" repeatCount="indefinite"/>
        </g>

        <!-- Titik data -->
        <circle cx="130" cy="110" r="3" fill="#f43f5e" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="270" cy="190" r="3" fill="#f43f5e" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2.5s" repeatCount="indefinite"/>
        </circle>
    </svg>
</div>
