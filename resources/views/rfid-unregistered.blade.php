<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Daftar Kartu Tidak Terdaftar — Monitoring Absensi</title>
  @vite(['resources/css/app.css'])
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    /* Smooth modal animation: initial (hidden) -> show (visible) */
    .modal-panel {
      transform: translateY(-8px) scale(.99);
      opacity: 0;
      transition: transform 180ms cubic-bezier(.2,.9,.2,1), opacity 180ms cubic-bezier(.2,.9,.2,1);
      will-change: transform, opacity;
    }
    .modal-panel.show {
      transform: translateY(0) scale(1);
      opacity: 1;
    }
    /* small responsive tweak for layout spacing */
    @media (min-width: 768px) {
      .filters-row { gap: 1rem; }
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">
  <div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold">Konfirmasi Kartu Tidak Terdaftar</h1>
        <p class="text-sm text-gray-600">Daftar kartu RFID yang belum dipetakan ke data pegawai. Daftarkan atau hapus sesuai kebutuhan.</p>
      </div>
      <div>
        <a href="{{ route('monitoring.index') }}" class="px-4 py-2 bg-amber-400 text-white rounded hover:bg-amber-500 cursor-pointer shadow text-sm flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 9a1 1 0 0 1-1-1V5.061a1 1 0 0 0-1.811-.75l-6.835 6.836a1.207 1.207 0 0 0 0 1.707l6.835 6.835a1 1 0 0 0 1.811-.75V16a1 1 0 0 1 1-1h6a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1z"/></svg>
            Kembali ke Monitoring
        </a>
      </div>
    </div>

    {{-- Top controls: showing selector (independent) and filters (search/status/date & buttons) --}}
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between filters-row">
      <!-- Left: Showing selector (independent) -->
      <div class="flex items-center space-x-2 mb-3 md:mb-0">
        <label class="text-sm text-gray-600">Tampilkan</label>
        @php $opts = ['10','30','50','all']; @endphp
        <select id="per_page_select" class="bg-white rounded border-gray-300 p-2 shadow-sm">
          @foreach($opts as $opt)
            <option value="{{ $opt }}" @if(($perPageParam ?? '10') == $opt) selected @endif>
              {{ $opt == 'all' ? 'Semua' : $opt }}
            </option>
          @endforeach
        </select>
        <span class="text-sm text-gray-600">baris</span>
      </div>

      <!-- Right: Filter form (search/status/date + Cari/Reset) -->
      <form id="filter-form" method="GET" action="{{ route('rfid.unregistered.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
        <!-- searchbar -->
        <input type="text" name="q" value="{{ old('q', $q ?? '') }}" placeholder="Cari ID (mis. RFID001)" class="p-2 shadow-sm rounded bg-white w-full sm:w-64" />
        <!-- filter status -->
        <select name="status" class="p-2 shadow-sm rounded bg-white">
          <option value="">Semua status</option>
          <option value="pending" @if(($status ?? '') === 'pending') selected @endif>pending</option>
          <option value="registered" @if(($status ?? '') === 'registered') selected @endif>registered</option>
          <option value="ignored" @if(($status ?? '') === 'ignored') selected @endif>ignored</option>
        </select>
        <!-- filter tanggal -->
        <input type="date" name="date" value="{{ old('date', $date ?? '') }}" class="p-2 shadow-sm rounded bg-white" />
        <!-- button cari -->
        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 inline-flex items-center gap-1 cursor-pointer">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
          <span class="ml-1">Cari</span>
        </button>
        <!-- button reset -->
        <a href="{{ route('rfid.unregistered.index') }}" class="px-4 py-2 border border-gray-400 rounded text-gray-700 hover:bg-gray-100 inline-flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          Reset
        </a>
      </form>
    </div>

    {{-- Table --}}
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
      <div class="px-4 py-3 border-b">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-600">Total: <strong>{{ $items->total() }}</strong> (Halaman {{ $items->currentPage() }})</div>
          <div class="text-sm text-gray-500">*Status pending berarti belum ditindaklanjuti</div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="text-center text-xs text-gray-500 uppercase tracking-wider">
              <th class="px-4 py-3 w-16">No</th>
              <th class="px-4 py-3">ID Kartu</th>
              <th class="px-4 py-3">Waktu Terdeteksi</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Tindakan</th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-gray-200">
            @forelse($items as $i => $row)
              <tr class="text-sm">
                <td class="px-4 py-3 align-middle text-center">
                  {{ $items->firstItem() + $i }}
                </td>
                <td class="px-4 py-3 align-middle font-medium text-center">
                  {{ $row->rfid_uid }}
                </td>
                <td class="px-4 py-3 align-middle text-gray-600 text-center">
                  {{ $row->detected_at ? $row->detected_at->format('Y-m-d H:i:s') : $row->created_at->format('Y-m-d H:i:s') }}
                </td>
                <td class="px-4 py-3 align-middle text-center">
                  @if($row->status === 'pending')
                    <span class="inline-block px-2 py-1 text-xs bg-amber-100 text-amber-800 rounded">pending</span>
                  @elseif($row->status === 'registered')
                    <span class="inline-block px-2 py-1 text-xs bg-green-100 text-green-800 rounded">registered</span>
                  @else
                    <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">ignored</span>
                  @endif
                </td>
                <td class="px-4 py-3 align-middle text-center">
                  <div class="inline-flex items-center space-x-2">
                    <!-- Register button -->
                    <button data-uid="{{ $row->rfid_uid }}" class="btn-register px-3 py-1 text-sm bg-sky-600 text-white rounded hover:bg-sky-700 cursor-pointer flex items-center gap-2"
                      type="button">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                      Daftarkan
                    </button>

                    <!-- Delete / ignore -->
                    <button data-id="{{ $row->id }}" class="btn-ignore px-3 py-1 text-sm border border-rose-400 text-rose-600 rounded hover:bg-rose-50 cursor-pointer flex items-center gap-2"
                      type="button">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 21H8a2 2 0 0 1-1.42-.587l-3.994-3.999a2 2 0 0 1 0-2.828l10-10a2 2 0 0 1 2.829 0l5.999 6a2 2 0 0 1 0 2.828L12.834 21"/><path d="m5.082 11.09 8.828 8.828"/></svg>
                      Abaikan
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada data.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination + info --}}
      <div class="px-4 py-3 bg-gray-50 border-t flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="text-sm text-gray-600">
          Menampilkan {{ $items->firstItem() ?? 0 }} sampai {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} data
        </div>
        <div class="flex items-center space-x-3">
          {{-- previous --}}
          @if ($items->onFirstPage())
            <span class="px-3 py-1 rounded border border-gray-300 text-gray-400 text-sm flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
              Previous
            </span>
          @else
            <a href="{{ $items->previousPageUrl() }}" class="px-3 py-1 rounded border border-orange-400 bg-white hover:bg-orange-50 text-sm text-orange-400 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
              Previous
            </a>
          @endif

          <div class="text-sm text-gray-600">Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</div>

          {{-- next --}}
          @if ($items->hasMorePages())
            <a href="{{ $items->nextPageUrl() }}" class="px-3 py-1 rounded border border-orange-400 bg-white hover:bg-orange-50 text-sm text-orange-400 flex items-center gap-2">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg>
            </a>
          @else
            <span class="px-3 py-1 rounded border border-gray-300 text-gray-400 text-sm flex items-center gap-2">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right-icon lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg>
            </span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Register Modal (custom) -->
  <div id="modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40">
    <div id="modal-panel" class="modal-panel bg-white rounded-lg shadow-lg w-full max-w-lg">
      <div class="px-6 py-4 border-b">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium">Daftarkan Kartu RFID</h3>
          <button id="modal-close" class="text-gray-500 hover:text-gray-800">&times;</button>
        </div>
      </div>

      <form id="register-form" class="px-6 py-4">
        <div class="mb-3">
          <label class="block text-sm text-gray-700 mb-1">ID Kartu</label>
          <input id="modal-uid" name="rfid_uid" readonly class="w-full p-2 border rounded bg-gray-100" />
        </div>

        <div class="mb-3">
          <label class="block text-sm text-gray-700 mb-1">Nama Pegawai</label>
          <input id="modal-nama" name="nama_pegawai" required class="w-full p-2 border rounded" placeholder="Masukkan nama pegawai" />
        </div>

        <div class="flex items-center justify-between mt-8">
          <button type="button" id="modal-cancel" class="px-4 py-2 border rounded text-sm hover:bg-gray-100 cursor-pointer inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            Batal
          </button>
          <button type="submit" id="modal-submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded text-sm cursor-pointer inline-flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
            Daftarkan
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      // Elements
      const perPageSelect = document.getElementById('per_page_select');
      const modal = document.getElementById('modal');
      const modalPanel = document.getElementById('modal-panel');
      const modalUid = document.getElementById('modal-uid');
      const modalNama = document.getElementById('modal-nama');
      const modalClose = document.getElementById('modal-close');
      const modalCancel = document.getElementById('modal-cancel');
      const modalForm = document.getElementById('register-form');
      const modalSubmit = document.getElementById('modal-submit');

      // SweetAlert2 toast mixin
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        customClass: { popup: 'text-sm' }
      });

      // per-page selector: change query param per_page & reload while keeping other filters
      if (perPageSelect) {
        perPageSelect.addEventListener('change', (e) => {
          const selected = e.target.value;
          const url = new URL(window.location.href);
          const params = url.searchParams;
          params.set('per_page', selected);
          params.delete('page'); // reset page
          url.search = params.toString();
          window.location.href = url.toString();
        });
      }

      // Open modal helper (with smooth animation, disable background scroll)
      function openRegisterModal(uid) {
        modalUid.value = uid;
        modalNama.value = '';
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // ensure panel not shown then trigger show to animate
        modalPanel.classList.remove('show');
        requestAnimationFrame(() => {
          requestAnimationFrame(() => {
            modalPanel.classList.add('show');
          });
        });

        setTimeout(() => modalNama.focus(), 180);
      }

      function closeModal() {
        modalPanel.classList.remove('show');

        const handler = () => {
          modal.classList.add('hidden');
          document.body.classList.remove('overflow-hidden');
          modalPanel.removeEventListener('transitionend', handler);
        };
        modalPanel.addEventListener('transitionend', handler, { once: true });

        // fallback hide
        setTimeout(() => {
          if (!modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
          }
        }, 300);
      }

      // click outside to close
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          closeModal();
        }
      });

      // attach events using delegation for register/ignore buttons
      document.body.addEventListener('click', async (e) => {
        const reg = e.target.closest('.btn-register');
        const ign = e.target.closest('.btn-ignore');

        if (reg) {
          const uid = reg.getAttribute('data-uid');
          openRegisterModal(uid);
        }

        if (ign) {
          const id = ign.getAttribute('data-id');
          const result = await Swal.fire({
            title: 'Hapus log?',
            text: 'Log akan ditandai sebagai diabaikan. Lanjutkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
          });
          if (!result.isConfirmed) return;

          try {
            const res = await fetch(`{{ url('/api/rfid-unregistered') }}/${id}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
              }
            });
            if (res.ok) {
              Toast.fire({ icon: 'success', title: 'Log diabaikan' });
              setTimeout(() => location.reload(), 600);
            } else {
              Toast.fire({ icon: 'error', title: 'Gagal menghapus' });
            }
          } catch (err) {
            console.error(err);
            Toast.fire({ icon: 'error', title: 'Gagal koneksi' });
          }
        }
      });

      // modal close handlers
      modalClose.addEventListener('click', closeModal);
      modalCancel.addEventListener('click', closeModal);

      // submit register form (AJAX)
      modalForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const uid = modalUid.value.trim();
        const nama = modalNama.value.trim();
        if (!uid || !nama) {
          Toast.fire({ icon: 'error', title: 'Lengkapi data terlebih dahulu' });
          return;
        }

        modalSubmit.disabled = true;
        const origText = modalSubmit.innerText;
        modalSubmit.innerText = 'Memproses...';

        try {
          const res = await fetch('{{ url('/api/rfid-unregistered/register') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ rfid_uid: uid, nama_pegawai: nama })
          });

          if (res.ok) {
            Toast.fire({ icon: 'success', title: 'Pegawai berhasil didaftarkan' });
            closeModal();
            setTimeout(() => location.reload(), 600);
          } else {
            const data = await res.json().catch(()=>({}));
            Toast.fire({ icon: 'error', title: data.message || 'Gagal mendaftar' });
          }
        } catch (err) {
          console.error(err);
          Toast.fire({ icon: 'error', title: 'Gagal koneksi' });
        } finally {
          modalSubmit.disabled = false;
          modalSubmit.innerText = origText;
        }
      });
    });
  </script>
</body>
</html>
