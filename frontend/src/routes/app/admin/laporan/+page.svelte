<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let data = $state(null);
	let loading = $state(true);
	let error = $state('');

	onMount(async () => {
		if (!requireRole('admin')) return;
		try {
			const res = await api.get('/dashboard/admin');
			data = res.data;
		} catch (e) {
			error = e.message || 'Gagal memuat laporan';
		} finally { loading = false; }
	});

	const maxPoli = $derived(Math.max(...(data?.kunjungan_per_poli_hari_ini?.map((r) => Number(r.total)) ?? [1]), 1));
	const max7 = $derived(Math.max(...(data?.kunjungan_7_hari?.map((r) => Number(r.total)) ?? [1]), 1));

	function fmtDate(d) {
		try { return new Date(d).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' }); } catch { return d; }
	}
	function fmtRp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

	function exportCSV() {
		if (!data) return;
		const rows = [
			['Laporan Klinik', new Date().toLocaleDateString('id-ID')],
			[],
			['Ringkasan', 'Nilai'],
			['Pendapatan Hari Ini', String(data.pendapatan_hari_ini ?? 0)],
			['Kunjungan Hari Ini', String(data.kunjungan_hari_ini ?? 0)],
			['Total Pasien', String(data.total_pasien ?? 0)],
			['Total Dokter', String(data.total_dokter ?? 0)],
			['Antrian Menunggu', String(data.antrian_menunggu ?? 0)],
			['Pembayaran Belum Lunas', String(data.pembayaran_tertunda ?? 0)],
			[],
			['Kunjungan 7 Hari Terakhir'],
			['Tanggal', 'Jumlah'],
			...((data.kunjungan_7_hari ?? []).map((r) => [r.visit_date, String(r.total)])),
			[],
			['Kunjungan per Poli Hari Ini'],
			['Poli', 'Jumlah'],
			...((data.kunjungan_per_poli_hari_ini ?? []).map((r) => [r.poli, String(r.total)]))
		];
		const csv = rows.map((r) => r.map((c) => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
		const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'laporan-klinik-' + new Date().toISOString().slice(0, 10) + '.csv';
		a.click();
		URL.revokeObjectURL(url);
	}

	function printPDF() {
		window.print();
	}
</script>

<div class="flex flex-wrap items-start justify-between gap-3">
	<div>
		<h1 class="text-xl font-bold text-gray-800">Laporan</h1>
		<p class="text-sm text-gray-500">Sumber: <code>/dashboard/admin</code> — ringkasan pendapatan & kunjungan.</p>
		<p class="mt-1 text-xs text-gray-400">DFD 6.2 <span class="font-mono">Tampilkan Laporan</span> → <span class="font-mono">Laporan (grafik/tabel/pdf)</span> · Data dari D2/D6/D10/D11</p>
	</div>
	{#if data}
		<div class="flex gap-2 print:hidden">
			<button onclick={printPDF} class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-teal-700">🖨️ Cetak / Simpan PDF</button>
			<button onclick={exportCSV} class="rounded-xl border bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">⬇ Unduh CSV</button>
		</div>
	{/if}
</div>
<div class="mb-6"></div>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if error}
	<p class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</p>
{:else if data}
<div id="print-area">
	<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Pendapatan Hari Ini</p>
			<p class="mt-1 text-2xl font-bold text-teal-700">{fmtRp(data.pendapatan_hari_ini)}</p>
			<p class="mt-1 text-xs text-gray-400">Pembayaran lunas (paid_at hari ini)</p>
		</div>
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Kunjungan Hari Ini</p>
			<p class="mt-1 text-3xl font-bold text-gray-800">{data.kunjungan_hari_ini}</p>
			<p class="mt-1 text-xs text-gray-400">{new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
		</div>
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Total Pasien / Dokter</p>
			<p class="mt-1 text-2xl font-bold text-gray-800">{data.total_pasien} <span class="text-sm font-normal text-gray-500">pasien</span> · {data.total_dokter} <span class="text-sm font-normal text-gray-500">dokter</span></p>
			<p class="mt-1 text-xs text-gray-400">Antrian menunggu: {data.antrian_menunggu} · Belum bayar: {data.pembayaran_tertunda}</p>
		</div>
	</div>

	<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
		<!-- Kunjungan 7 hari bar -->
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="font-semibold text-gray-800">Kunjungan 7 Hari Terakhir</h2>
			<p class="mb-4 text-xs text-gray-500">Jumlah kunjungan per hari</p>
			{#if !data.kunjungan_7_hari?.length}
				<p class="rounded-lg bg-gray-50 px-3 py-6 text-center text-sm text-gray-500">Belum ada data.</p>
			{:else}
				<div class="space-y-3">
					{#each data.kunjungan_7_hari as row}
						<div class="flex items-center gap-3 text-sm">
							<span class="w-24 shrink-0 text-xs text-gray-500">{fmtDate(row.visit_date)}</span>
							<div class="flex-1 h-3 overflow-hidden rounded-full bg-gray-100">
								<div class="h-full rounded-full bg-teal-600" style="width: {(Number(row.total) / max7) * 100}%"></div>
							</div>
							<span class="w-8 text-right font-bold text-gray-800">{row.total}</span>
						</div>
					{/each}
				</div>
				<p class="mt-4 text-xs text-gray-400">Max harian: {max7} kunjungan</p>
			{/if}
		</div>

		<!-- Per poli -->
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="font-semibold text-gray-800">Kunjungan per Poli Hari Ini</h2>
			<p class="mb-4 text-xs text-gray-500">Distribusi pasien berdasarkan poli</p>
			{#if !data.kunjungan_per_poli_hari_ini?.length}
				<p class="rounded-lg bg-gray-50 px-3 py-6 text-center text-sm text-gray-500">Belum ada kunjungan hari ini.</p>
			{:else}
				<div class="space-y-3">
					{#each data.kunjungan_per_poli_hari_ini as row}
						<div>
							<div class="mb-1 flex items-center justify-between text-sm">
								<span class="font-medium text-gray-700">{row.poli}</span>
								<span class="font-bold text-gray-800">{row.total}</span>
							</div>
							<div class="h-2.5 overflow-hidden rounded-full bg-gray-100">
								<div class="h-full rounded-full bg-slate-700" style="width: {(Number(row.total) / maxPoli) * 100}%"></div>
							</div>
						</div>
					{/each}
				</div>
			{/if}
		</div>
	</div>
	</div>
{/if}

<style>
	@media print {
		:global(nav), :global(aside) { display: none !important; }
		:global(main) { padding: 0 !important; }
	}
</style>
