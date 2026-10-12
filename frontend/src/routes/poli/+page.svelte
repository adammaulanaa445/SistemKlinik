<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let polyclinics = $state([]);
	let loading = $state(true);

	const poliMeta = [
		{ emoji: '🩺', bg: 'bg-teal-100', text: 'text-teal-700', ring: 'ring-teal-200' },
		{ emoji: '🦷', bg: 'bg-sky-100', text: 'text-sky-700', ring: 'ring-sky-200' },
		{ emoji: '👁️', bg: 'bg-violet-100', text: 'text-violet-700', ring: 'ring-violet-200' },
		{ emoji: '❤️', bg: 'bg-rose-100', text: 'text-rose-700', ring: 'ring-rose-200' },
		{ emoji: '👶', bg: 'bg-amber-100', text: 'text-amber-700', ring: 'ring-amber-200' },
		{ emoji: '🦴', bg: 'bg-orange-100', text: 'text-orange-700', ring: 'ring-orange-200' },
		{ emoji: '🧠', bg: 'bg-emerald-100', text: 'text-emerald-700', ring: 'ring-emerald-200' },
		{ emoji: '🧴', bg: 'bg-cyan-100', text: 'text-cyan-700', ring: 'ring-cyan-200' }
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

<section class="bg-[#0f3d3e] px-4 py-12 text-center text-white md:py-14">
	<h1 class="text-2xl font-bold tracking-tight md:text-3xl">Poli Kami</h1>
	<p class="mx-auto mt-2 max-w-xl text-sm text-teal-100/90">Pilih poli sesuai kebutuhan kesehatan Anda</p>
</section>

<section class="mx-auto max-w-6xl px-4 py-8 md:py-10">
	{#if loading}
		<div class="grid grid-cols-2 gap-4 md:grid-cols-4">
			{#each Array(8) as _}
				<div class="h-36 animate-pulse rounded-2xl bg-slate-100"></div>
			{/each}
		</div>
	{:else if polyclinics.length === 0}
		<p class="rounded-2xl border border-dashed bg-white p-10 text-center text-sm text-slate-500">Belum ada data poli.</p>
	{:else}
		<div class="grid grid-cols-2 gap-4 md:grid-cols-4">
			{#each polyclinics as poli, i}
				{@const meta = poliMeta[i % poliMeta.length]}
				<a
					href="/poli/{poli.id}"
					class="group flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-6 text-center shadow-sm transition hover:border-teal-200 hover:shadow-md"
				>
					<div class="flex h-14 w-14 items-center justify-center rounded-full text-2xl shadow-sm ring-1 {meta.bg} {meta.ring}">
						{meta.emoji}
					</div>
					<h3 class="mt-3 text-sm font-bold text-slate-800 group-hover:text-teal-700">{poli.name}</h3>
					<p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">{poli.description ?? 'Layanan poli spesialis'}</p>
					<span class="mt-3 inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">
						<span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
						{poli.doctors_count ?? 0} dokter
					</span>
				</a>
			{/each}
		</div>
	{/if}

	<!-- Fallback: ensure 8 visual spots if API returns fewer (show placeholders) -->
	{#if !loading && polyclinics.length > 0 && polyclinics.length < 8}
		<p class="mt-4 text-center text-xs text-slate-400">Menampilkan {polyclinics.length} poli tersedia</p>
	{/if}
</section>
