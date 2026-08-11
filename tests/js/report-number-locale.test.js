import assert from 'node:assert/strict';

await import('../../public/js/components/report-number-locale.js');

const numberLocale = globalThis.KssReportNumber;

assert.ok(numberLocale, 'Pemformat angka harus tersedia.');

const expectedSequence = [
    '1',
    '10',
    '100',
    '1.000',
    '10.000',
    '100.000',
    '1.000.000',
];

let displayedValue = '';
for (const [index, digit] of [...'1000000'].entries()) {
    displayedValue = numberLocale.format(displayedValue + digit, {
        mode: 'locale',
        preserveFraction: true,
    });

    assert.equal(displayedValue, expectedSequence[index]);
}

assert.equal(
    numberLocale.format('1.0000', { mode: 'locale', preserveFraction: true }),
    '10.000',
    'Titik hasil format tidak boleh berubah menjadi koma ketika nol berikutnya diketik.'
);

assert.equal(
    numberLocale.format('1000,50', { mode: 'locale', preserveFraction: true }),
    '1.000,50'
);

assert.equal(
    numberLocale.format('1234.50', { mode: 'flexible', preserveFraction: true }),
    '1.234,50',
    'Nilai awal/database bertitik desimal tetap harus dapat dibaca.'
);

assert.equal(
    numberLocale.format('1,234.50', { mode: 'flexible', preserveFraction: true }),
    '1.234,50',
    'Angka hasil paste berformat internasional harus dinormalisasi.'
);

assert.equal(
    numberLocale.canonical('1.234.567,89', { mode: 'locale' }),
    '1234567.89'
);

assert.equal(
    numberLocale.canonical('2.147.483.647', { mode: 'locale', maximum: 2147483647 }),
    '2147483647'
);

console.log('Report number locale regression tests passed.');
