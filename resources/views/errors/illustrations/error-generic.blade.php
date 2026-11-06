<!-- Ilustrasi generik: Puzzle yang tidak lengkap -->
<div class="error-illustration">
    <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
        <defs>
            <linearGradient id="puzzleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#64748b"/>
                <stop offset="100%" stop-color="#475569"/>
            </linearGradient>
            <filter id="shadowGeneric" x="-20%" y="-20%" width="140%" height="140%">
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

        <!-- Potongan puzzle -->
        <g transform="translate(130, 100)" filter="url(#shadowGeneric)">
            <path d="M0 0 L60 0 Q80 0 80 20 L100 20 Q120 20 120 40 L120 60 Q120 80 100 80 L80 80 Q60 80 60 100 L40 100 Q20 100 20 80 L20 60 Q20 40 40 40 L40 20 Q60 20 60 0 Z" fill="#94a3b8"/>
        </g>

        <g transform="translate(210, 100)" filter="url(#shadowGeneric)">
            <path d="M0 0 L60 0 Q80 0 80 20 L100 20 Q120 20 120 40 L120 60 Q120 80 100 80 L80 80 Q60 80 60 100 L40 100 Q20 100 20 80 L20 60 Q20 40 40 40 L40 20 Q60 20 60 0 Z" fill="#cbd5e1"/>
        </g>

        <g transform="translate(170, 160)" filter="url(#shadowGeneric)">
            <path d="M0 0 L60 0 Q80 0 80 20 L100 20 Q120 20 120 40 L120 60 Q120 80 100 80 L80 80 Q60 80 60 100 L40 100 Q20 100 20 80 L20 60 Q20 40 40 40 L40 20 Q60 20 60 0 Z" fill="#e2e8f0"/>
        </g>

        <!-- Potongan hilang (kosong dengan tepi putus) -->
        <g transform="translate(250, 160)">
            <path d="M0 0 L60 0 Q80 0 80 20 L100 20 Q120 20 120 40 L120 60 Q120 80 100 80 L80 80 Q60 80 60 100 L40 100 Q20 100 20 80 L20 60 Q20 40 40 40 L40 20 Q60 20 60 0 Z" fill="none" stroke="#cbd5e1" stroke-width="2" stroke-dasharray="5 5"/>
            <path d="M30 30 L50 30 M30 50 L50 50 M30 30 L30 50 M50 30 L50 50" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
            <animateTransform attributeName="transform" type="scale" values="1;0.95;1" dur="1.5s" repeatCount="indefinite"/>
        </g>

        <!-- Titik di sekitar -->
        <circle cx="150" cy="80" r="3" fill="#64748b" opacity="0.6">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
        </circle>
        <circle cx="250" cy="220" r="3" fill="#64748b" opacity="0.6">
            <animate attributeName="opacity" values="0.3;1;0.3" dur="2.6s" repeatCount="indefinite"/>
        </circle>
    </svg>
</div>
