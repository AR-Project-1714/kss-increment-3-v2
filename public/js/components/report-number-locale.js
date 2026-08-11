(function (root, factory) {
    const api = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }

    if (root) {
        root.KssReportNumber = api;
    }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    const DEFAULT_MAXIMUM = 9999999999999.99;

    function splitNumber(value, mode = 'flexible') {
        let text = String(value ?? '').trim().replace(/[^\d.,-]/g, '');
        if (!text || !/\d/.test(text)) return null;

        const negative = text.startsWith('-');
        text = text.replace(/-/g, '');

        const lastDot = text.lastIndexOf('.');
        const lastComma = text.lastIndexOf(',');
        let decimalAt = -1;

        if (mode === 'locale') {
            // Di dalam field yang sudah dilokalkan, titik selalu pemisah ribuan
            // dan hanya koma yang boleh menjadi pemisah desimal.
            decimalAt = lastComma;
        } else if (lastDot >= 0 && lastComma >= 0) {
            // Data lama/paste dapat memakai format Indonesia atau internasional.
            decimalAt = Math.max(lastDot, lastComma);
        } else {
            const separatorAt = Math.max(lastDot, lastComma);
            if (separatorAt >= 0) {
                const separator = lastDot >= 0 ? '.' : ',';
                const separatorCount = text.split(separator).length - 1;
                const trailingDigits = text.slice(separatorAt + 1).replace(/\D/g, '').length;

                // Satu separator dengan tiga angka di belakang paling mungkin
                // merupakan pengelompokan ribuan dari data lama.
                if (separatorCount === 1 && trailingDigits !== 3) {
                    decimalAt = separatorAt;
                }
            }
        }

        const integerSource = decimalAt >= 0 ? text.slice(0, decimalAt) : text;
        const fractionSource = decimalAt >= 0 ? text.slice(decimalAt + 1) : '';
        const integerDigits = integerSource.replace(/\D/g, '').replace(/^0+(?=\d)/, '') || '0';
        const fractionDigits = fractionSource.replace(/\D/g, '').slice(0, 2);

        return {
            negative,
            integerDigits,
            fractionDigits,
            hasDecimal: decimalAt >= 0,
        };
    }

    function parse(value, options = {}) {
        const parts = splitNumber(value, options.mode || 'flexible');
        if (!parts) return null;

        const normalized = parts.integerDigits
            + (parts.hasDecimal && parts.fractionDigits ? `.${parts.fractionDigits}` : '');
        const number = Number(normalized);

        if (!Number.isFinite(number)) return null;
        return parts.negative ? -number : number;
    }

    function groupedInteger(integerDigits) {
        return integerDigits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function partsFromSafeNumber(number) {
        const fixed = String(number);
        const [integerDigits, fractionDigits = ''] = fixed.split('.');

        return {
            negative: false,
            integerDigits,
            fractionDigits: fractionDigits.slice(0, 2),
            hasDecimal: fractionDigits.length > 0,
        };
    }

    function format(value, options = {}) {
        const mode = options.mode || 'flexible';
        const preserveFraction = options.preserveFraction === true;
        const maximum = Number.isFinite(options.maximum) ? options.maximum : DEFAULT_MAXIMUM;
        let parts = splitNumber(value, mode);

        if (!parts) return '';

        const parsed = parse(value, { mode });
        if (parsed === null) return '';

        const safeNumber = Math.min(maximum, Math.max(0, parsed));
        const wasClamped = parts.negative || parsed > maximum;
        if (wasClamped) parts = partsFromSafeNumber(safeNumber);

        let result = groupedInteger(parts.integerDigits);

        if (preserveFraction) {
            if (parts.hasDecimal) result += `,${parts.fractionDigits}`;
        } else {
            const safeParts = partsFromSafeNumber(safeNumber);
            if (safeParts.fractionDigits) result += `,${safeParts.fractionDigits}`;
        }

        return result;
    }

    function canonical(value, options = {}) {
        const mode = options.mode || 'locale';
        const maximum = Number.isFinite(options.maximum) ? options.maximum : DEFAULT_MAXIMUM;
        const number = parse(value, { mode });

        return number === null ? '' : String(Math.min(maximum, Math.max(0, number)));
    }

    return {
        DEFAULT_MAXIMUM,
        splitNumber,
        parse,
        format,
        canonical,
    };
});
