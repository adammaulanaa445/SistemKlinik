<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let polyclinics = $state([]);
	let loading = $state(true);

	const layananUtama = [
		{ nama: 'Pemeriksaan Umum', ikon: '🩺' },
		{ nama: 'Konsultasi Dokter', ikon: '👨‍⚕️' },
		{ nama: 'Farmasi', ikon: '💊' },
		{ nama: 'Rekam Medis', ikon: '📋' },
		{ nama: 'Jadwal Dokter', ikon: '📅' }
	];

	onMount(async () => {
		try {
			const res = await api.get('/polyclinics');
			polyclinics = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="bg-teal-800 px-4 py-14 text-center text-white">
	<h1 class="text-3xl font-bold">Layanan Kesehatan Lengkap untuk Anda</h1>
	<p class="mx-auto mt-3 max-w-xl text-teal-100">
		Kami menyediakan berbagai layanan kesehatan untuk kebutuhan Anda dan keluarga.
	</p>
</section>

<section class="mx-auto max-w-6xl px-4 py-12">
	<h2 class="mb-4 text-lg font-semibold text-gray-800">Layanan Kami</h2>
	<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5">
		{#each layananUtama as layanan}
			<div class="rounded-xl border bg-white p-5 text-center shadow-sm">
				<div
					class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-2xl"
				>
					{layanan.ikon}
				</div>
				<p class="text-sm font-medium text-gray-700">{layanan.nama}</p>
			</div>
		{/each}
	</div>

	<h2 class="mt-12 mb-4 text-lg font-semibold text-gray-800">Poli Kami</h2>

	{#if loading}
		<p class="text-sm text-gray-500">Memuat data poli...</p>
	{:else}
		<div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
			{#each polyclinics as poli}
				<div class="rounded-xl border bg-white p-5 shadow-sm">
					<h3 class="font-semibold text-gray-800">{poli.name}</h3>
					<p class="mt-1 text-sm text-gray-500">{poli.description ?? '-'}</p>
					<p class="mt-2 text-xs text-teal-700">{poli.doctors_count} dokter tersedia</p>
				</div>
			{/each}
		</div>
	{/if}
</section>