<!-- Ilustrasi 429: Banyak orang / antre -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="userGrad429" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#fb7185"/>
                <stop offset="100%" stop-color="#e11d48"/>
            </linearGradient>
            <filter id="shadow429" x="-20%" y="-20%" width="140%" height="140%">
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

        <!-- Lingkaran pembatas -->
        <circle cx="200" cy="150" r="100" fill="#fecaca" opacity="0.15" stroke="#fb7185" stroke-width="1" stroke-dasharray="10 10"/>
        <rect x="130" y="60" width="140" height="140" rx="20" fill="#fee2e2" opacity="0.1" stroke="#fb7185" stroke-width="1" stroke-dasharray="6 6"/>

        <!-- Antre orang -->
        <g transform="translate(170, 140)" filter="url(#shadow429)">
            <!-- Orang 1 -->
            <circle cx="0" cy="-30" r="16" fill="url(#userGrad429)"/>
            <path d="M-18 5 Q0 20 18 5" fill="none" stroke="#fb7185" stroke-width="4" stroke-linecap="round"/>
            <animateTransform attributeName="transform" type="translate" values="0 0;-2 0;2 0;0 0" dur="1.5s" repeatCount="indefinite"/>

            <!-- Orang 2 -->
            <g transform="translate(40, 0)">
                <circle cx="0" cy="-25" r="14" fill="url(#userGrad429)" opacity="0.9"/>
                <path d="M-14 5 Q0 16 14 5" fill="none" stroke="#fb7185" stroke-width="4" stroke-linecap="round" opacity="0.9"/>
                <animateTransform attributeName="transform" type="translate" values="0 0;2 0;-2 0;0 0" dur="1.8s" repeatCount="indefinite"/>
            </g>

            <!-- Orang 3 -->
            <g transform="translate(80, 0)">
                <circle cx="0" cy="-20" r="12" fill="url(#userGrad429)" opacity="0.8"/>
                <path d="M-12 5 Q0 12 12 5" fill="none" stroke="#fb7185" stroke-width="4" stroke-linecap="round" opacity="0.8"/>
                <animateTransform attributeName="transform" type="translate" values="0 0;-1 0;1 0;0 0" dur="1.3s" repeatCount="indefinite"/>
            </g>
        </g>

        <!-- Ikon stop / tangan -->
        <g transform="translate(230, 190)" opacity="0.9">
            <circle cx="0" cy="0" r="22" fill="#ef4444"/>
            <path d="M-8 -8 L8 8 M8 -8 L-8 8" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
            <animate attributeName="opacity" values="1;0.6;1" dur="1s" repeatCount="indefinite"/>
        </g>

        <!-- Titik di sekitar -->
        <circle cx="120" cy="100" r="3" fill="#fb7185" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="280" cy="200" r="3" fill="#fb7185" opacity="0.7">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2.6s" repeatCount="indefinite"/>
        </circle>
    </svg>
</div>
