<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" href="{{ asset('assets/images/ciamislogo.png') }}">
    <title>Monitoring Absensi Piket</title>
    @vite(['resources/css/app.css'])
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SweetAlert2 (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="max-w-6xl mx-auto p-6">
        {{-- HEADER + BELL --}}
        <div class="md:flex md:justify-between items-start">
            <div>
                <header class="mb-6">
                    <h1 class="text-2xl font-semibold">Monitoring Absensi Piket</h1>
                    <p class="mt-1 text-sm text-gray-600">Tabel absensi piket: check-in & check-out</p>
                </header>
            </div>

            {{-- Bell + Dropdown --}}
            <div class="relative">
                <button id="bell-btn" class="relative p-2 rounded hover:bg-gray-100 focus:outline-none cursor-pointer" aria-label="Notifikasi RFID">
                    <!-- bell svg -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell">
                        <path d="M10.268 21a2 2 0 0 0 3.464 0"/>
                        <path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>
                    </svg>

                    <span id="bell-badge" class="hidden absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1">0</span>
                </button>

                <!-- dropdown -->
                <div id="bell-dropdown" class="hidden absolute right-0 mt-2 w-96 bg-white shadow-lg rounded-lg z-50">
                    <div class="p-3 border-b font-medium bg-teal-500 text-white rounded-t-lg">Notifikasi Kartu Tidak Terdaftar</div>
                    <div id="bell-list" class="max-h-64 overflow-auto p-2 text-sm">
                        <!-- items injected by JS -->
                        <div class="text-center text-gray-500 py-4">Memuat...</div>
                    </div>
                    <div class="border-t p-3 text-center">
                        <a href="{{ route('rfid.unregistered.index') }}" class="text-sm text-teal-500 hover:underline">Selengkapnya</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- RFID listener panel --}}
        <div class="mb-4 flex items-center justify-between space-x-4">
            <div class="flex items-center space-x-3">
                <label class="text-sm text-gray-600">Mode RFID:</label>
                <div id="rfid-status" class="text-sm px-3 py-1 rounded bg-green-100 text-green-800">Siap menerima (Focus pada halaman)</div>
            </div>

            <div class="flex items-center space-x-3">
                <div id="last-scan" class="text-sm text-gray-600">Belum ada tap</div>
                <button id="focus-btn" type="button" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">Fokuskan input</button>
            </div>
        </div>

        <!-- Invisible input yang akan diisi oleh USB RFID reader (keyboard emulator) -->
        <input id="rfid-input" type="text" autocomplete="off" class="absolute opacity-0 pointer-events-none" aria-hidden="true">

        {{-- Controls: per-page selector + Search form --}}
        <form method="GET" action="{{ route('monitoring.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-2 md:justify-between gap-3 items-center">
            {{-- Showing (per page) --}}
            <div class="flex items-center space-x-2">
                <label for="per_page" class="text-sm text-gray-600">Tampilkan</label>
                <select id="per_page" name="per_page" class="bg-white rounded border-gray-300 p-2 shadow-sm" onchange="this.form.submit()">
                    @php $opts = [5,10,15,25,50]; @endphp
                    @foreach($opts as $opt)
                        <option value="{{ $opt }}" @if(($perPage ?? 5) == $opt) selected @endif>{{ $opt }}</option>
                    @endforeach
                </select>
                <span class="text-sm text-gray-600">baris</span>
            </div>

            <div class="flex items-center space-x-3">
                {{-- Search text --}}
                <div class="flex items-center">
                    <input
                        type="text"
                        name="q"
                        value="{{ old('q', $q ?? '') }}"
                        placeholder="Cari nama atau ID (mis. Rizki / RFID001)"
                        class="w-full rounded border-gray-300 shadow-sm focus:ring-2 focus:ring-amber-200 p-2 bg-white"
                    >
                </div>

                {{-- Date + Buttons --}}
                <div class="flex items-center space-x-2">
                    <input
                        type="date"
                        name="date"
                        value="{{ old('date', $date ?? '') }}"
                        class="rounded border-gray-300 shadow-sm focus:ring-2 focus:ring-amber-200 p-2 bg-white"
                    >

                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 cursor-pointer">
                        <!-- search icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14z"/>
                        </svg>
                        Cari
                    </button>

                    <a href="{{ route('monitoring.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-400 rounded text-gray-700 hover:bg-gray-100">
                        <!-- reset icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Reset
                    </a>
                </div>

            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Nama Pegawai</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Check-in</th>
                            <th class="px-4 py-3">Check-out</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($absensis as $index => $item)
                            <tr class="whitespace-wrap text-sm text-gray-700">
                                <td class="px-4 py-3 text-center">
                                    {{ $absensis->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ optional($item->pegawai)->rfid_uid ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ optional($item->pegawai)->nama_pegawai ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $item->tanggal?->format('Y-m-d') ?? $item->tanggal }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $item->check_in ? $item->check_in->format('Y-m-d H:i:s') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ $item->check_out ? $item->check_out->format('Y-m-d H:i:s') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                    Tidak ada data absensi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-400 flex items-center justify-between flex-col md:flex-row space-y-2 md:space-y-0">
                <div class="text-xs text-gray-600">
                    Menampilkan {{ $absensis->firstItem() ?? 0 }} sampai {{ $absensis->lastItem() ?? 0 }} dari {{ $absensis->total() }} data
                </div>

                <div class="flex items-center space-x-2">
                    {{-- Previous button --}}
                    @if ($absensis->onFirstPage())
                        <span class="px-3 py-1 rounded border border-gray-400 text-gray-400 bg-white text-sm flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
                            Previous
                        </span>
                    @else
                        <a href="{{ $absensis->previousPageUrl() }}" class="px-3 py-1 rounded border border-orange-400 bg-white hover:bg-orange-50 text-sm text-orange-400 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
                            Previous
                        </a>
                    @endif

                    {{-- Current page / last page --}}
                    <span class="text-xs text-gray-600">Halaman {{ $absensis->currentPage() }} dari {{ $absensis->lastPage() }}</span>

                    {{-- Next button --}}
                    @if ($absensis->hasMorePages())
                        <a href="{{ $absensis->nextPageUrl() }}" class="px-3 py-1 rounded border border-orange-400 bg-white hover:bg-orange-50 text-sm text-orange-400 flex items-center gap-2">
                            Next
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @else
                        <span class="px-3 py-1 rounded border border-gray-400 text-gray-400 bg-white text-sm flex items-center gap-2">
                            Next
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Toast container (kept for compatibility) -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
</body>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const input = document.getElementById('rfid-input');
    const statusEl = document.getElementById('rfid-status');
    const lastScanEl = document.getElementById('last-scan');
    const focusBtn = document.getElementById('focus-btn');

    const bellBtn = document.getElementById('bell-btn');
    const bellBadge = document.getElementById('bell-badge');
    const bellDropdown = document.getElementById('bell-dropdown');
    const bellList = document.getElementById('bell-list');
    const toastContainer = document.getElementById('toast-container');

    // CSRF token
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : null;

    // set up SweetAlert2 toast helper
    function showToast(message, icon = 'info', ttl = 2500) {
        // icon: 'success' | 'error' | 'warning' | 'info' | 'question'
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: ttl,
            icon: icon,
            title: message,
            customClass: { popup: 'text-sm' }
        });
    }

    // ensure input exists and focus
    function focusInput() {
        input.focus();
        statusEl.textContent = 'Siap menerima (fokus aktif)';
        statusEl.className = 'text-sm px-3 py-1 rounded bg-green-100 text-green-800';
    }

    focusBtn.addEventListener('click', () => focusInput());
    focusInput();

    // Fetch pending logs (count + latest 5)
    async function fetchPending() {
        try {
            const res = await fetch('{{ url('/api/rfid-unregistered/pending') }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const json = await res.json();
            const count = json.count || 0;

            if (count > 0) {
                bellBadge.textContent = count;
                bellBadge.classList.remove('hidden');
            } else {
                bellBadge.classList.add('hidden');
            }

            // populate list
            bellList.innerHTML = '';
            const latest = json.latest || [];
            if (latest.length === 0) {
                bellList.innerHTML = '<div class="text-center text-gray-500 py-4">Tidak ada notifikasi</div>';
            } else {
                latest.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'flex items-start justify-between p-3 border-b hover:bg-gray-50';

                    row.innerHTML = `
                        <div class="flex items-start space-x-3">
                            <!-- Icon kiri -->
                            <div class="mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-credit-card-icon lucide-credit-card"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                            </div>

                            <!-- Info -->
                            <div>
                                <div class="font-medium text-sm">${item.rfid_uid}</div>
                                <div class="text-xs text-gray-500">
                                    ${new Date(item.detected_at).toLocaleString()}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center space-x-2">
                            <!-- Register (icon) -->
                            <button
                                title="Daftarkan"
                                data-uid="${item.rfid_uid}"
                                class="register-btn p-1 rounded hover:bg-sky-100 text-sky-600 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-plus-icon lucide-user-plus"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                            </button>

                            <!-- Ignore (icon) -->
                            <button
                                title="Abaikan"
                                data-id="${item.id}"
                                class="ignore-btn p-1 rounded hover:bg-red-100 text-red-600 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eraser-icon lucide-eraser"><path d="M21 21H8a2 2 0 0 1-1.42-.587l-3.994-3.999a2 2 0 0 1 0-2.828l10-10a2 2 0 0 1 2.829 0l5.999 6a2 2 0 0 1 0 2.828L12.834 21"/><path d="m5.082 11.09 8.828 8.828"/></svg>
                            </button>
                        </div>
                    `;

                    bellList.appendChild(row);
                });
            }
        } catch (err) {
            console.error('fetchPending error', err);
        }
    }

    // Toggle dropdown
    bellBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        bellDropdown.classList.toggle('hidden');
        if (!bellDropdown.classList.contains('hidden')) {
            await fetchPending();
        }
    });

    // Close dropdown if click outside
    document.addEventListener('click', () => {
        bellDropdown.classList.add('hidden');
    });

    // Delegate click on bellList (register / ignore) - using closest to support SVG clicks
    bellList.addEventListener('click', async (e) => {
        const ignoreBtn = e.target.closest('.ignore-btn');
        const registerBtn = e.target.closest('.register-btn');

        // IGNORE (use SweetAlert2 confirm)
        if (ignoreBtn) {
            const id = ignoreBtn.getAttribute('data-id');
            const { isConfirmed } = await Swal.fire({
                title: 'Hapus log?',
                text: 'Log akan ditandai sebagai diabaikan. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            });
            if (!isConfirmed) return;

            try {
                const res = await fetch(`{{ url('/api/rfid-unregistered') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    showToast('Log dihapus', 'success');
                    await fetchPending();
                } else {
                    showToast('Gagal menghapus', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal koneksi', 'error');
            }
        }

        // REGISTER (use SweetAlert2 input modal)
        if (registerBtn) {
            const uid = registerBtn.getAttribute('data-uid');

            const { value: nama } = await Swal.fire({
                title: `Daftarkan UID ${uid}`,
                input: 'text',
                inputLabel: 'Nama Pegawai',
                inputPlaceholder: 'Masukkan nama pegawai',
                showCancelButton: true,
                confirmButtonText: 'Daftarkan',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Nama pegawai wajib diisi';
                    }
                }
            });

            if (!nama) return; // cancelled

            // send register request
            try {
                const res = await fetch('{{ url('/api/rfid-unregistered/register') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        rfid_uid: uid,
                        nama_pegawai: nama
                    })
                });

                if (res.ok) {
                    showToast('Pegawai berhasil didaftarkan', 'success');
                    await fetchPending();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const data = await res.json().catch(() => ({}));
                    showToast(data.message || 'Gagal mendaftar', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Gagal koneksi', 'error');
            }
        }
    });

    // Polling every 8s
    setInterval(fetchPending, 8000);
    fetchPending(); // initial

    // RFID input handler (reader emulates keyboard + Enter)
    input.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();

            const raw = input.value.trim();
            input.value = ''; // reset input
            if (!raw) return;

            // tampilkan sementara
            lastScanEl.textContent = `Terbaca: ${raw}`;
            statusEl.textContent = 'Memproses...';
            statusEl.className = 'text-sm px-3 py-1 rounded bg-amber-100 text-amber-800';

            try {
                const res = await fetch('{{ url('/api/absensi/tap') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}),
                    },
                    body: JSON.stringify({ rfid_uid: raw })
                });

                const data = await res.json().catch(() => ({}));

                // handle 'unregistered' explicitly (server returns 200 with status 'unregistered')
                if (data && data.status === 'unregistered') {
                    statusEl.textContent = data.message || 'Kartu belum terdaftar';
                    statusEl.className = 'text-sm px-3 py-1 rounded bg-red-100 text-red-700';
                    lastScanEl.textContent = `Terbaca: ${raw} — belum terdaftar`;
                    showToast('Kartu belum terdaftar', 'warning');

                    // update badge/list
                    await fetchPending();
                } else if (res.ok) {
                    // normal success: check_in / check_out
                    statusEl.textContent = data.message || 'Tercatat';
                    statusEl.className = 'text-sm px-3 py-1 rounded bg-green-100 text-green-800';
                    lastScanEl.textContent = `Terbaca: ${raw} — ${data.status ?? ''}`;

                    // refresh monitoring table to show new data
                    const autoRefresh = true;
                    if (autoRefresh) {
                        setTimeout(() => { window.location.reload(); }, 700);
                    }
                } else {
                    // other errors (e.g. rejected 409)
                    statusEl.textContent = data.message || 'Terjadi error';
                    statusEl.className = 'text-sm px-3 py-1 rounded bg-red-100 text-red-700';
                    lastScanEl.textContent = `Terbaca: ${raw} — ${data.message ?? 'Error'}`;
                    showToast(data.message || 'Terjadi error', 'error');
                }
            } catch (err) {
                console.error(err);
                statusEl.textContent = 'Gagal kirim ke server';
                statusEl.className = 'text-sm px-3 py-1 rounded bg-red-100 text-red-700';
                lastScanEl.textContent = `Terbaca: ${raw} — (gagal koneksi)`;
                showToast('Gagal koneksi ke server', 'error');
            } finally {
                // fokus kembali
                setTimeout(() => input.focus(), 100);
            }
        }
    });

    // smarter click handler: only refocus RFID input when user didn't click a form control,
    // the bell dropdown, or when SweetAlert2 is open. This prevents stealing focus
    // from search input or SweetAlert2 modals.
    document.addEventListener('click', (e) => {
        try {
            // if SweetAlert2 modal is open, DON'T refocus
            if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) {
                return;
            }

            // if click was inside bell dropdown (so user interacts with items) DON'T refocus
            if (e.target.closest && e.target.closest('#bell-dropdown')) {
                return;
            }

            // if click was inside any form control (input, textarea, select, button) DON'T refocus
            if (e.target.closest && e.target.closest('input, textarea, select, button, [contenteditable="true"]')) {
                return;
            }

            // otherwise it's safe to keep focus on the RFID input
            if (document.activeElement !== input) {
                input.focus();
            }
        } catch (err) {
            // fail-safe: don't break app if something unexpected happens
            console.error('focus handler error', err);
        }
    });

});
</script>
</html>
