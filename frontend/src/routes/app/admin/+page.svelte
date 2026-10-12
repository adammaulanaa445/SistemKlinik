<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole, auth } from '$lib/auth.svelte.js';

	let data = $state(null);
	let loading = $state(true);
	let error = $state('');

	onMount(async () => {
		if (!requireRole('admin')) return;
		try {
			const res = await api.get('/dashboard/admin');
			data = res.data;
		} catch (e) {
			error = e.message || 'Gagal memuat dashboard';
		} finally {
			loading = false;
		}
	});

	const maxPoli = $derived(
		Math.max(...(data?.kunjungan_per_poli_hari_ini?.map((r) => Number(r.total)) ?? [1]), 1)
	);
	const max7 = $derived(
		Math.max(...(data?.kunjungan_7_hari?.map((r) => Number(r.total)) ?? [1]), 1)
	);

	function fmtDate(d) {
		try {
			return new Date(d).toLocaleDateString('id-ID', {
				weekday: 'short',
				day: 'numeric',
				month: 'short'
			});
		} catch {
			return d;
		}
	}
</script>

<h1 class="text-xl font-bold text-gray-800">Selamat datang, {auth.user?.name} 👋</h1>
<p class="mb-6 text-sm text-gray-500">Ringkasan operasional klinik hari ini.</p>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if error}
	<p class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</p>
{:else if data}
	<!-- Statistik utama -->
	<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Total Pasien</p>
			<p class="mt-1 text-3xl font-bold text-gray-800">{data.total_pasien}</p>
			<p class="mt-1 text-xs text-gray-400">Terdaftar di sistem</p>
		</div>
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Total Dokter</p>
			<p class="mt-1 text-3xl font-bold text-gray-800">{data.total_dokter}</p>
			<p class="mt-1 text-xs text-gray-400">Aktif</p>
		</div>
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Kunjungan Hari Ini</p>
			<p class="mt-1 text-3xl font-bold text-teal-700">{data.kunjungan_hari_ini}</p>
			<p class="mt-1 text-xs text-gray-400">{new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
		</div>
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Antrian Menunggu</p>
			<p class="mt-1 text-3xl font-bold text-amber-600">{data.antrian_menunggu}</p>
			<p class="mt-1 text-xs text-gray-400">Perlu dipanggil</p>
		</div>
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Pendapatan Hari Ini</p>
			<p class="mt-1 text-2xl font-bold text-teal-700">Rp {Number(data.pendapatan_hari_ini).toLocaleString('id-ID')}</p>
			<p class="mt-1 text-xs text-gray-400">Pembayaran lunas</p>
		</div>
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="text-xs font-medium text-gray-500">Pembayaran Tertunda</p>
			<p class="mt-1 text-3xl font-bold text-red-600">{data.pembayaran_tertunda}</p>
			<p class="mt-1 text-xs text-gray-400">Belum bayar</p>
		</div>
	</div>

	<!-- Dua kolom: per poli + 7 hari -->
	<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
		<!-- Kunjungan per Poli -->
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="mb-1 font-semibold text-gray-800">Kunjungan per Poli Hari Ini</h2>
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
							<div class="h-2 overflow-hidden rounded-full bg-gray-100">
								<div class="h-full rounded-full bg-teal-600" style="width: {(Number(row.total) / maxPoli) * 100}%"></div>
							</div>
						</div>
					{/each}
				</div>
			{/if}
		</div>

		<!-- Tren 7 hari -->
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="mb-1 font-semibold text-gray-800">Tren 7 Hari Terakhir</h2>
			<p class="mb-4 text-xs text-gray-500">Jumlah kunjungan harian</p>
			{#if !data.kunjungan_7_hari?.length}
				<p class="rounded-lg bg-gray-50 px-3 py-6 text-center text-sm text-gray-500">Belum ada data.</p>
			{:else}
				<div class="space-y-2">
					{#each data.kunjungan_7_hari as row}
						<div class="flex items-center gap-3 text-sm">
							<span class="w-24 shrink-0 text-xs text-gray-500">{fmtDate(row.visit_date)}</span>
							<div class="flex-1 h-2 overflow-hidden rounded-full bg-gray-100">
								<div class="h-full rounded-full bg-slate-700" style="width: {(Number(row.total) / max7) * 100}%"></div>
							</div>
							<span class="w-8 text-right font-medium text-gray-800">{row.total}</span>
						</div>
					{/each}
				</div>
			{/if}
		</div>
	</div>

	<!-- Akses cepat -->
	<div class="mt-6 rounded-xl border bg-white p-5 shadow-sm">
		<h2 class="mb-3 font-semibold text-gray-800">Akses Cepat</h2>
		<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
			<a href="/app/petugas" class="rounded-lg border px-4 py-3 hover:bg-gray-50">
				<p class="text-sm font-medium text-gray-800">🧾 Antrian & Pembayaran</p>
				<p class="text-xs text-gray-500">Panggil pasien, catat bayar</p>
			</a>
			<a href="/app/farmasi" class="rounded-lg border px-4 py-3 hover:bg-gray-50">
				<p class="text-sm font-medium text-gray-800">💊 Resep & Obat</p>
				<p class="text-xs text-gray-500">Proses & serahkan resep</p>
			</a>
			<div class="rounded-lg border border-dashed px-4 py-3 opacity-60">
				<p class="text-sm font-medium text-gray-600">🗂️ Data Master</p>
				<p class="text-xs text-gray-400">Poli, dokter, pengguna — segera</p>
			</div>
		</div>
	</div>
{/if}
