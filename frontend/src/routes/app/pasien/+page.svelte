<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { auth, requireRole } from '$lib/auth.svelte.js';

	let visits = $state([]);
	let loading = $state(true);

	const statusLabel = {
		menunggu: 'Menunggu',
		dipanggil: 'Dipanggil',
		diproses: 'Diperiksa',
		diperiksa: 'Diperiksa',
		selesai: 'Selesai'
	};
	const statusColor = {
		menunggu: 'bg-amber-100 text-amber-700 ring-amber-200',
		dipanggil: 'bg-blue-100 text-blue-700 ring-blue-200',
		diproses: 'bg-teal-100 text-teal-700 ring-teal-200',
		diperiksa: 'bg-teal-100 text-teal-700 ring-teal-200',
		selesai: 'bg-emerald-100 text-emerald-700 ring-emerald-200'
	};

	onMount(async () => {
		if (!requireRole('pasien')) return;
		try {
			const res = await api.get('/visits/my');
			visits = res.data;
		} finally {
			loading = false;
		}
	});

	const aktif = $derived(visits.filter((v) => v.status !== 'selesai' && v.status !== 'batal'));
	const riwayat = $derived(visits.filter((v) => v.status === 'selesai'));
	const kunjunganHariIni = $derived(aktif.slice(0, 3));
</script>

<div class="mx-auto max-w-4xl">
	<h1 class="text-xl font-bold tracking-tight text-slate-900">Selamat datang, {auth.user?.name} 👋</h1>
	<p class="mt-1 text-sm text-slate-500">Semoga Anda selalu sehat. Pantau kunjungan dan antrian Anda di sini.</p>

	{#if loading}
		<p class="mt-8 rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else}
		<!-- 3 stat cards ala mockup screen 8 -->
		<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
			<div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
				<div class="flex items-center gap-3">
					<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-100">📋</span>
					<p class="text-xs font-medium text-slate-500">Kunjungan</p>
				</div>
				<p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{visits.length}</p>
				<p class="text-xs text-slate-400">Total kunjungan Anda</p>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-teal-50">
				<div class="flex items-center gap-3">
					<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-600 text-white shadow">🔢</span>
					<p class="text-xs font-medium text-slate-500">Antrian</p>
				</div>
				<p class="mt-3 text-3xl font-extrabold tracking-tight text-teal-600">{aktif[0]?.queue?.queue_number ?? '—'}</p>
				<p class="text-xs text-slate-400">{aktif[0] ? (statusLabel[aktif[0].queue?.status ?? aktif[0].status] ?? '-') : 'Tidak ada antrian aktif'}</p>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
				<div class="flex items-center gap-3">
					<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">🕘</span>
					<p class="text-xs font-medium text-slate-500">Riwayat</p>
				</div>
				<p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{riwayat.length}</p>
				<p class="text-xs text-slate-400">Kunjungan selesai</p>
			</div>
		</div>

		<!-- Kunjungan Hari Ini ala mockup -->
		<div class="mt-8 flex items-center justify-between">
			<h2 class="text-sm font-bold tracking-tight text-slate-900">Kunjungan Hari Ini</h2>
			<a href="/app/kunjungan" class="rounded-full bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-teal-700">+ Daftar Kunjungan</a>
		</div>

		{#if kunjunganHariIni.length === 0}
			<div class="mt-3 rounded-2xl border border-dashed bg-white p-8 text-center shadow-sm">
				<p class="text-sm font-medium text-slate-700">Belum ada kunjungan berjalan</p>
				<p class="mx-auto mt-1 max-w-md text-xs leading-relaxed text-slate-500">Klik “Daftar Kunjungan” untuk berobat. Pilih poli → dokter → jadwal, isi keluhan, nomor antrian terbit otomatis.</p>
			</div>
		{:else}
			<div class="mt-3 space-y-3">
				{#each kunjunganHariIni as v}
					<div class="flex flex-col gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
						<div class="min-w-0">
							<p class="text-sm font-semibold text-slate-900">{v.polyclinic.name}</p>
							<p class="text-xs text-slate-500">{v.doctor.user.name} · {v.polyclinic.queue_code ?? ''}-{v.queue?.queue_number ?? '-'}</p>
							<p class="mt-1 text-xs text-slate-400">{new Date(v.visit_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })} · {v.complaint?.slice(0, 40) ?? ''}</p>
						</div>
						<div class="flex items-center gap-3">
							<span class="rounded-full px-3 py-1 text-xs font-medium ring-1 {statusColor[v.queue?.status ?? v.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'}">
								{statusLabel[v.queue?.status ?? v.status] ?? v.status}
							</span>
							<p class="hidden text-lg font-extrabold tracking-tight text-teal-600 sm:block">{v.queue?.queue_number ?? '-'}</p>
							<a href="/app/antrian/{v.id}" class="rounded-full bg-teal-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-teal-700">Lihat Detail</a>
						</div>
					</div>
				{/each}
				{#if aktif.length > 3}
					<a href="/app/antrian" class="block text-center text-xs font-medium text-teal-700 hover:underline">Lihat semua antrian ({aktif.length}) →</a>
				{/if}
			</div>
		{/if}

		<!-- Link cepat ke riwayat -->
		{#if riwayat.length > 0}
			<div class="mt-6 rounded-2xl border bg-slate-50 p-4">
				<div class="flex items-center justify-between">
					<p class="text-sm font-semibold text-slate-800">Riwayat terakhir</p>
					<a href="/app/riwayat" class="text-xs font-medium text-teal-700 hover:underline">Lihat riwayat →</a>
				</div>
				<p class="mt-1 text-xs text-slate-500">{riwayat.length} kunjungan selesai — cek rekam medis dan pembayaran di halaman Riwayat.</p>
			</div>
		{/if}
	{/if}
</div>
