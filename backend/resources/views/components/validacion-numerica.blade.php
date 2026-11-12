<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-solo-enteros]').forEach((el) => {
            el.addEventListener('input', () => {
                const limpio = el.value.replace(/\D+/g, '');
                if (el.value !== limpio) el.value = limpio;
            });
        });

        document.querySelectorAll('[data-solo-decimal]').forEach((el) => {
            el.addEventListener('input', () => {
                let v = el.value.replace(/[^\d.,]/g, '');
                const sepIndex = v.search(/[.,]/);
                if (sepIndex !== -1) {
                    const entero = v.slice(0, sepIndex);
                    const sep = v[sepIndex];
                    const dec = v.slice(sepIndex + 1).replace(/[.,]/g, '').slice(0, 2);
                    v = entero + sep + dec;
                }
                if (el.value !== v) el.value = v;
            });
        });

        document.querySelectorAll('[data-solo-coord]').forEach((el) => {
            el.addEventListener('input', () => {
                let v = el.value.replace(/[^\d.-]/g, '');
                const negativo = v.startsWith('-') ? '-' : '';
                v = v.replace(/-/g, '');
                const punto = v.indexOf('.');
                if (punto !== -1) {
                    v = v.slice(0, punto + 1) + v.slice(punto + 1).replace(/\./g, '');
                }
                const limpio = negativo + v;
                if (el.value !== limpio) el.value = limpio;
            });
        });
    });
</script>
