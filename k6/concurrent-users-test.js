// ============================================================================
//  PENGUJIAN BEBAN (LOAD TESTING) - 12 PENGGUNA SERENTAK
//  Sistem Manajemen Dokumen Operasional KSS (Laravel)
//
//  Tujuan : Memvalidasi sistem tetap aman & responsif ketika 12 pengguna
//           membuka website secara bersamaan (login + buka dashboard).
//  Alat   : k6 (https://k6.io) - Non-Functional / Performance / Load Testing.
//
//  Cara menjalankan:
//     1. Pastikan server berjalan, mis. `php artisan serve` (localhost:8000)
//        atau virtual host Laragon (mis. http://increment-3.test).
//     2. Pastikan database sudah di-seed (akun di bawah harus ada).
//     3. Jalankan:
//          k6 run k6/concurrent-users-test.js
//        atau dengan URL kustom:
//          k6 run -e BASE_URL=http://increment-3.test k6/concurrent-users-test.js
//        untuk menyimpan hasil ke file (dipakai di laporan):
//          k6 run --summary-export=k6/hasil-pengujian.json k6/concurrent-users-test.js
// ============================================================================

import http from 'k6/http';
import { check, sleep, group, fail } from 'k6';
import { Trend, Rate } from 'k6/metrics';
import exec from 'k6/execution';

// ----------------------------------------------------------------------------
//  Konfigurasi dasar
// ----------------------------------------------------------------------------
const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8000').replace(/\/+$/, '');

// 12 akun nyata yang ada di sistem (sesuai database/seeders/DatabaseSeeder.php).
// Setiap Virtual User (VU) dipetakan ke SATU akun berbeda, sehingga benar-benar
// meniru 12 pengguna unik yang mengakses sistem secara bersamaan.
const USERS = [
  { username: 'admin',             password: 'password', home: '/admin',         peran: 'Admin' },
  { username: 'manajer',           password: 'password', home: '/manajer',       peran: 'Manajer' },
  { username: 'karu.a',            password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'wakaru.a',          password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'karu.b',            password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'wakaru.b',          password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'karu.c',            password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'wakaru.c',          password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'karu.d',            password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'wakaru.d',          password: 'password', home: '/report-ops',    peran: 'Operasional' },
  { username: 'kasi.pemeliharaan', password: 'password', home: '/pemeliharaan',  peran: 'Pemeliharaan' },
  { username: 'karu.safety',       password: 'password', home: '/report-safety', peran: 'Safety' },
];

// ----------------------------------------------------------------------------
//  Metrik khusus untuk laporan (di luar metrik bawaan k6)
// ----------------------------------------------------------------------------
const loginDuration = new Trend('waktu_login', true);        // durasi proses login (ms)
const pageDuration  = new Trend('waktu_buka_dashboard', true); // durasi buka dashboard (ms)
const loginSuccess  = new Rate('rasio_login_berhasil');       // persentase login sukses

// ----------------------------------------------------------------------------
//  Opsi pengujian: skenario + threshold (ambang kelulusan)
// ----------------------------------------------------------------------------
export const options = {
  // 12 VU = 12 pengguna serentak. Pola: berdatangan -> aktif bersamaan -> selesai.
  scenarios: {
    dua_belas_pengguna_serentak: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '10s', target: 12 }, // ramp-up: 12 pengguna berdatangan
        { duration: '30s', target: 12 }, // steady: 12 pengguna aktif bersamaan
        { duration: '5s',  target: 0 },  // ramp-down: pengguna selesai
      ],
      gracefulRampDown: '5s',
    },
  },

  // Threshold = kriteria PASS/FAIL otomatis. Inilah angka yang ditulis di laporan.
  thresholds: {
    http_req_failed:        ['rate<0.01'],            // < 1% request gagal (idealnya 0%)
    http_req_duration:      ['p(95)<500', 'p(99)<1000'], // 95% request < 500ms
    waktu_login:            ['p(95)<800'],            // 95% login < 800ms
    waktu_buka_dashboard:   ['p(95)<600'],            // 95% buka dashboard < 600ms
    rasio_login_berhasil:   ['rate>0.99'],            // > 99% login berhasil
    checks:                 ['rate>0.99'],            // > 99% validasi lolos
  },
};

// ----------------------------------------------------------------------------
//  State per-VU (variabel modul = unik & persisten per VU lintas iterasi)
// ----------------------------------------------------------------------------
let account = null;
let isLoggedIn = false;

// Ambil nilai token CSRF (_token) dari HTML halaman login.
function extractCsrf(html) {
  const match = html.match(/name="_token"\s+value="([^"]+)"/);
  return match ? match[1] : null;
}

// ----------------------------------------------------------------------------
//  setup(): pemanasan server agar route/view ter-compile sebelum diukur.
// ----------------------------------------------------------------------------
export function setup() {
  const res = http.get(`${BASE_URL}/login`);
  check(res, { 'server siap (halaman login 200)': (r) => r.status === 200 });
}

// ----------------------------------------------------------------------------
//  Langkah 1: Login (sekali per VU)
// ----------------------------------------------------------------------------
function login(user) {
  // a. Buka halaman login -> dapatkan cookie sesi + token CSRF.
  const getRes = http.get(`${BASE_URL}/login`, { tags: { langkah: 'buka_halaman_login' } });
  check(getRes, { 'halaman login tampil (200)': (r) => r.status === 200 });

  const token = extractCsrf(getRes.body);
  if (!token) {
    loginSuccess.add(false);
    fail('Token CSRF tidak ditemukan pada halaman login - cek BASE_URL/markup.');
  }

  // b. Submit kredensial. k6 otomatis mengikuti redirect (302 -> dashboard).
  const postRes = http.post(
    `${BASE_URL}/login`,
    { _token: token, username: user.username, password: user.password },
    { tags: { langkah: 'submit_login' }, redirects: 5 }
  );

  loginDuration.add(postRes.timings.duration);

  const ok = check(postRes, {
    'login berhasil (status 200)': (r) => r.status === 200,
    'tidak dilempar balik ke halaman login': (r) => !r.url.endsWith('/login'),
  });

  loginSuccess.add(ok);
  return ok;
}

// ----------------------------------------------------------------------------
//  Langkah 2: Buka dashboard sesuai peran (memakai sesi yang sudah login)
// ----------------------------------------------------------------------------
function browse(user) {
  group('buka_dashboard', function () {
    const res = http.get(`${BASE_URL}${user.home}`, { tags: { langkah: 'dashboard' } });
    pageDuration.add(res.timings.duration);
    check(res, {
      'dashboard terbuka (200)': (r) => r.status === 200,
      'sesi masih aktif (tidak ke login)': (r) => !r.url.endsWith('/login'),
    });
  });
}

// ----------------------------------------------------------------------------
//  Alur utama tiap iterasi VU
// ----------------------------------------------------------------------------
export default function () {
  // Petakan VU -> 1 akun unik (VU #1 -> USERS[0], dst.).
  if (account === null) {
    account = USERS[(exec.vu.idInTest - 1) % USERS.length];
  }

  // Login cukup SEKALI per VU. Ini realistis (pengguna login lalu menavigasi)
  // sekaligus menghormati rate-limit login (20 percobaan/IP per 300 detik).
  if (!isLoggedIn) {
    group('login', function () {
      isLoggedIn = login(account);
    });
    if (!isLoggedIn) {
      sleep(1);
      return; // gagal login -> lewati, coba lagi iterasi berikutnya
    }
  }

  // Pengguna aktif menavigasi sistem.
  browse(account);

  sleep(1); // think time: jeda berpikir manusia antar-aksi
}
