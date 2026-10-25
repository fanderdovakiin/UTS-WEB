/**
 * RuangStok — perilaku UI.
 * Semua data bisnis (harga, total, stok) tetap dihitung server; berkas ini
 * hanya untuk kenyamanan tampilan: drawer sidebar, menu profil, jam, baris
 * item transaksi, dan pengaman double-submit.
 */

// --- Drawer sidebar (layar kecil) ------------------------------------------
const sidebar = document.getElementById('app-sidebar');
const overlay = document.getElementById('sidebar-overlay');

function bukaSidebar(buka) {
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('is-open', buka);
    overlay.classList.toggle('is-open', buka);
    document.body.style.overflow = buka && window.innerWidth < 768 ? 'hidden' : '';
}

document.querySelectorAll('[data-buka-sidebar]').forEach((tombol) => {
    tombol.addEventListener('click', () => bukaSidebar(true));
});

document.querySelectorAll('[data-tutup-sidebar]').forEach((tombol) => {
    tombol.addEventListener('click', () => bukaSidebar(false));
});

overlay?.addEventListener('click', () => bukaSidebar(false));

window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') bukaSidebar(false);
});

// --- Grup menu di sidebar --------------------------------------------------
document.querySelectorAll('[data-group-toggle]').forEach((tombol) => {
    const targetId = tombol.getAttribute('aria-controls');
    const panel = targetId ? document.getElementById(targetId) : null;
    if (!panel) return;

    tombol.addEventListener('click', () => {
        const terbuka = tombol.getAttribute('aria-expanded') === 'true';
        tombol.setAttribute('aria-expanded', String(!terbuka));
        panel.hidden = terbuka;
    });
});

// --- Menu profil di top header --------------------------------------------
const profilTombol = document.querySelector('[data-profil-tombol]');
const profilPanel = document.getElementById('profil-panel');

profilTombol?.addEventListener('click', (event) => {
    event.stopPropagation();
    profilPanel?.classList.toggle('is-open');
    profilTombol.setAttribute(
        'aria-expanded',
        String(profilPanel?.classList.contains('is-open') ?? false),
    );
});

document.addEventListener('click', (event) => {
    if (!profilPanel?.classList.contains('is-open')) return;
    if (profilPanel.contains(event.target) || profilTombol?.contains(event.target)) return;
    profilPanel.classList.remove('is-open');
    profilTombol?.setAttribute('aria-expanded', 'false');
});

// --- Jam berjalan di top header --------------------------------------------
const jam = document.getElementById('topbar-jam');

if (jam) {
    const angka = jam.querySelector('[data-jam-angka]');
    const tanggal = jam.querySelector('[data-jam-tanggal]');
    const zona = jam.querySelector('[data-jam-zona]');

    const pemformatJam = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
    const pemformatTanggal = new Intl.DateTimeFormat('id-ID', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
    const pemformatZona = new Intl.DateTimeFormat('id-ID', {
        timeZoneName: 'short',
    });

    const detak = () => {
        const sekarang = new Date();
        if (angka) angka.textContent = pemformatJam.format(sekarang).replace(/\./g, '.');
        if (tanggal) tanggal.textContent = '| ' + pemformatTanggal.format(sekarang);
        if (zona) {
            const bagian = pemformatZona.formatToParts(sekarang)
                .find((bagian) => bagian.type === 'timeZoneName');
            zona.textContent = bagian ? bagian.value : '';
        }
    };

    detak();
    window.setInterval(detak, 1000);
}

// --- Baris item transaksi (pembelian & penjualan) -------------------------
document.querySelectorAll('[data-form-transaksi]').forEach((wadah) => {
    const daftarBaris = wadah.querySelector('[data-daftar-baris]');
    const template = wadah.querySelector('#item-row-template');
    const tombolTambah = wadah.querySelector('[data-tambah-baris]');
    const totalItem = wadah.querySelector('[data-total-item]');
    const totalNilai = wadah.querySelector('[data-total-transaksi]');
    const adaHarga = wadah.dataset.hargaMode === 'beli' ? 'purchase_price' : 'selling_price';

    if (!daftarBaris || !template) return;

    const rupiah = (nilai) =>
        new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(nilai || 0);

    function segarkanNomorBaris() {
        daftarBaris.querySelectorAll('.item-row').forEach((baris, posisi) => {
            const label = baris.querySelector('[data-nomor-baris]');
            if (label) label.textContent = 'Item ' + (posisi + 1);
        });
    }

    function segarkanRingkasan() {
        let jumlahItem = 0;
        let total = 0;

        daftarBaris.querySelectorAll('.item-row').forEach((baris) => {
            const pilihan = baris.querySelector('[data-produk]');
            const kuantitas = baris.querySelector('[data-kuantitas]');
            const opsiTerpilih = pilihan?.selectedOptions?.[0];
            const harga = Number(opsiTerpilih?.dataset.harga ?? 0);
            const qty = Math.max(0, Number(kuantitas?.value ?? 0));
            const subtotal = harga * qty;

            jumlahItem += qty;
            total += subtotal;

            const labelSubtotal = baris.querySelector('[data-subtotal]');
            if (labelSubtotal) labelSubtotal.textContent = rupiah(subtotal);

            const labelInfo = baris.querySelector('[data-info]');
            if (labelInfo && opsiTerpilih?.value) {
                const stok = Number(opsiTerpilih.dataset.stok ?? 0);
                const satuan = opsiTerpilih.dataset.satuan ?? 'unit';
                const peringatan = qty > stok && adaHarga !== 'purchase_price';
                labelInfo.textContent = peringatan
                    ? `Stok tersedia: ${stok} ${satuan} — kuantitas melebihi stok, server akan menolak transaksi ini.`
                    : `Stok tersedia: ${stok} ${satuan} · Harga: ${rupiah(harga)}`;
                labelInfo.style.color = peringatan ? 'var(--app-danger-text)' : '';
            } else if (labelInfo) {
                labelInfo.textContent = '';
                labelInfo.style.color = '';
            }
        });

        if (totalItem) totalItem.textContent = String(jumlahItem);
        if (totalNilai) totalNilai.textContent = rupiah(total);
    }

    function pasangBaris(baris) {
        baris.querySelector('[data-hapus-baris]')?.addEventListener('click', () => {
            if (daftarBaris.querySelectorAll('.item-row').length <= 1) {
                baris.querySelector('[data-produk]').value = '';
                const qty = baris.querySelector('[data-kuantitas]');
                if (qty) qty.value = '1';
            } else {
                baris.remove();
            }
            segarkanNomorBaris();
            segarkanRingkasan();
        });

        baris.querySelector('[data-produk]')?.addEventListener('change', segarkanRingkasan);
        baris.querySelector('[data-kuantitas]')?.addEventListener('input', segarkanRingkasan);
    }

    tombolTambah?.addEventListener('click', () => {
        const isi = template.innerHTML.replaceAll('__INDEX__', String(Date.now()));
        const wadahBaris = document.createElement('div');
        wadahBaris.innerHTML = isi;
        const baris = wadahBaris.firstElementChild;

        daftarBaris.appendChild(baris);
        pasangBaris(baris);
        segarkanNomorBaris();
        segarkanRingkasan();
        baris.querySelector('[data-produk]')?.focus();
    });

    daftarBaris.querySelectorAll('.item-row').forEach(pasangBaris);
    segarkanNomorBaris();
    segarkanRingkasan();
});

// --- Konfirmasi aksi berisiko (hapus, batal transaksi) ---------------------
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    const pesan = form.dataset.konfirmasi;
    if (pesan && !window.confirm(pesan)) {
        event.preventDefault();
    }
});

// --- Pengaman double-submit -----------------------------------------------
document.querySelectorAll('form[data-submit-once]').forEach((form) => {
    form.addEventListener('submit', () => {
        const tombol = form.querySelector('[type="submit"]:not([disabled])');
        if (!tombol) return;
        tombol.dataset.labelAsli = tombol.textContent.trim();
        tombol.disabled = true;
        tombol.textContent = tombol.dataset.busy || 'Menyimpan…';
    });
});
