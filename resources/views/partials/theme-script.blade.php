<script>
    /*
     * Inisialisasi tema sebelum paint agar tidak ada kedipan putih
     * saat halaman dimuat dalam mode gelap.
     */
    (() => {
        const STORAGE_KEY = 'bestco_theme_mode';
        const VALID_MODES = ['light', 'dark', 'system'];
        const root = document.documentElement;
        const media = window.matchMedia('(prefers-color-scheme: dark)');

        const readMode = () => {
            try {
                const stored = window.localStorage.getItem(STORAGE_KEY);

                return VALID_MODES.includes(stored) ? stored : 'light';
            } catch (error) {
                return 'light';
            }
        };

        const resolveMode = (mode) => {
            if (mode === 'system') {
                return media.matches ? 'dark' : 'light';
            }

            return mode;
        };

        const applyMode = (mode, persist = false) => {
            const resolved = resolveMode(mode);
            const isDark = resolved === 'dark';

            root.classList.toggle('dark', isDark);
            root.dataset.themeMode = mode;
            root.style.colorScheme = resolved;

            const themeColor = document.querySelector('meta[name="theme-color"]');
            if (themeColor) {
                themeColor.setAttribute('content', isDark ? '#070d17' : '#ffffff');
            }

            if (persist) {
                try {
                    window.localStorage.setItem(STORAGE_KEY, mode);
                } catch (error) {
                    // localStorage bisa diblokir; tema tetap berlaku untuk sesi ini.
                }
            }

            window.dispatchEvent(new CustomEvent('bestco-theme-changed', {
                detail: { mode, isDark },
            }));
        };

        applyMode(readMode());

        const onSystemChange = () => {
            if (readMode() === 'system') {
                applyMode('system');
            }
        };

        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', onSystemChange);
        } else if (typeof media.addListener === 'function') {
            media.addListener(onSystemChange);
        }

        window.BestcoTheme = {
            get mode() {
                return readMode();
            },
            get isDark() {
                return root.classList.contains('dark');
            },
            set(mode) {
                if (VALID_MODES.includes(mode)) {
                    applyMode(mode, true);
                }
            },
            toggle() {
                applyMode(root.classList.contains('dark') ? 'light' : 'dark', true);
            },
        };
    })();
</script>
