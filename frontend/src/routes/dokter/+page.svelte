<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let doctors = $state([]);
	let loading = $state(true);
	let search = $state('');

	let filtered = $derived(
		doctors.filter((d) => {
			if (!search.trim()) return true;
			const q = search.toLowerCase();
			return (
				d.user.name.toLowerCase().includes(q) ||
				(d.specialization ?? '').toLowerCase().includes(q) ||
				(d.polyclinic?.name ?? '').toLowerCase().includes(q)
			);
		})
	);

	onMount(async () => {
		try {
			const res = await api.get('/doctors');
			doctors = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="mx-auto max-w-6xl px-4 py-8 md:py-10">
	<!-- Header -->
	<div class="text-center">
		<h1 class="text-2xl font-bold text-slate-800 md:text-3xl">Dokter Kami</h1>
		<p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">Temukan dokter terbaik sesuai kebutuhan Anda</p>
	</div>

	<!-- Search bar -->
	<div class="mx-auto mt-6 max-w-2xl">
		<div class="relative">
			<span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z" /></svg>
			</span>
			<input
				bind:value={search}
				placeholder="Cari dokter, spesialisasi, atau poli..."
				class="w-full rounded-full border border-slate-200 bg-white py-3 pr-4 pl-11 text-sm text-slate-700 placeholder:text-slate-400 shadow-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
			/>
		</div>
	</div>

	{#if loading}
		<div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
			{#each Array(3) as _}
				<div class="h-56 animate-pulse rounded-2xl bg-slate-100"></div>
			{/each}
		</div>
	{:else if filtered.length === 0}
		<p class="mt-10 rounded-2xl border border-dashed bg-white p-10 text-center text-sm text-slate-500">Tidak ada dokter ditemukan.</p>
	{:else}
		<div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
			{#each filtered as doctor}
				<div class="flex flex-col rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:shadow-md">
					<!-- Avatar + badge -->
					<div class="flex flex-col items-center text-center">
						<div class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-teal-100 to-cyan-100 text-xl font-bold text-teal-700 ring-4 ring-teal-50">
							{doctor.user.name.charAt(0).toUpperCase()}
						</div>
						<span class="mt-3 inline-flex rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">
							{doctor.polyclinic?.name ?? 'Poli Umum'}
						</span>
						<h3 class="mt-2 text-[15px] font-bold text-slate-800">{doctor.user.name}</h3>
						<p class="text-sm text-slate-500">{doctor.specialization ?? 'Dokter Umum'}</p>
					</div>

					<div class="mt-5 border-t border-slate-100 pt-4">
						<div class="flex items-center justify-center gap-1.5 text-xs text-slate-400">
							<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
							SistemKlinik • Tersedia
						</div>
					</div>

					<a
						href="/dokter/{doctor.id}"
						class="mt-4 block w-full rounded-xl bg-teal-600 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
					>
						Lihat Profil
					</a>
				</div>
			{/each}
		</div>
	{/if}
</section>
