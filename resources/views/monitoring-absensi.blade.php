<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('assets/images/ciamislogo.png') }}">
    <title>Monitoring Absensi Piket</title>
    @vite(['resources/css/app.css'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="max-w-6xl mx-auto p-6">
        <div class="md:flex md:justify-between">
            <div>
                <header class="mb-6">
                    <h1 class="text-2xl font-semibold">Monitoring Absensi Piket</h1>
                    <p class="mt-1 text-sm text-gray-600">Tabel absensi piket: check-in & check-out</p>
                </header>
            </div>
            <div class="hover:text-red-500 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bell-icon lucide-bell"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
            </div>
        </div>

        {{-- RFID listener panel (letakkan di bawah header) --}}
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
        <input id="rfid-input" type="text" autocomplete="off"
            class="absolute opacity-0 pointer-events-none" aria-hidden="true">


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
                        <span class="px-3 py-1 rounded border border-gray-400 text-gray-400 bg-white text-sm">Previous</span>
                    @else
                        <a href="{{ $absensis->previousPageUrl() }}" class="px-3 py-1 rounded border border-gray-400 bg-white hover:bg-gray-100 text-sm">Previous</a>
                    @endif

                    {{-- Current page / last page --}}
                    <span class="text-xs text-gray-600">Halaman {{ $absensis->currentPage() }} dari {{ $absensis->lastPage() }}</span>

                    {{-- Next button --}}
                    @if ($absensis->hasMorePages())
                        <a href="{{ $absensis->nextPageUrl() }}" class="px-3 py-1 rounded border border-gray-400 bg-white hover:bg-gray-100 text-sm">Next</a>
                    @else
                        <span class="px-3 py-1 rounded border border-gray-400 text-gray-400 bg-white text-sm">Next</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('rfid-input');
  const statusEl = document.getElementById('rfid-status');
  const lastScanEl = document.getElementById('last-scan');
  const focusBtn = document.getElementById('focus-btn');

  // Ambil CSRF token dari meta (jika ada)
  const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : null;

  // Pastikan input fokus saat halaman siap
  function focusInput() {
    // input.classList.remove('opacity-0'); // jangan tunjukkan input
    input.focus();
    statusEl.textContent = 'Siap menerima (fokus aktif)';
    statusEl.className = 'text-sm px-3 py-1 rounded bg-green-100 text-green-800';
  }

  focusBtn.addEventListener('click', () => {
    focusInput();
  });

  // Fokus otomatis saat load
  focusInput();

  // Handler saat Enter ditekan di input (reader biasanya mengirimkan Enter otomatis)
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
            // kirim CSRF kalau ada (aman untuk dev)
            ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}),
          },
          body: JSON.stringify({ rfid_uid: raw })
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
          // sukses
          statusEl.textContent = data.message || 'Tercatat';
          statusEl.className = 'text-sm px-3 py-1 rounded bg-green-100 text-green-800';
          lastScanEl.textContent = `Terbaca: ${raw} — ${data.status ?? ''}`;

          // opsi: reload supaya data baru muncul di tabel
          // kamu bisa set ke true/false sesuai kebutuhan
          const autoRefresh = true;
          if (autoRefresh) {
            // beri 700ms jeda biar user lihat pesan
            setTimeout(() => { window.location.reload(); }, 700);
          }
        } else {
          // error (mis. kartu tidak terdaftar)
          statusEl.textContent = data.message || 'Terjadi error';
          statusEl.className = 'text-sm px-3 py-1 rounded bg-red-100 text-red-700';
          lastScanEl.textContent = `Terbaca: ${raw} — ${data.message ?? 'Error'}`;
        }
      } catch (err) {
        console.error(err);
        statusEl.textContent = 'Gagal kirim ke server';
        statusEl.className = 'text-sm px-3 py-1 rounded bg-red-100 text-red-700';
        lastScanEl.textContent = `Terbaca: ${raw} — (gagal koneksi)`;
      } finally {
        // fokus kembali
        setTimeout(() => input.focus(), 100);
      }
    }
  });

  // jika fokus hilang, kembalikan fokus saat area diklik
  document.addEventListener('click', () => {
    if (document.activeElement !== input) {
      // jangan paksa jika user sedang isi form lain
      input.focus();
    }
  });
});
</script>

</html>
