<script>
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let poli = $state(null);
	let loading = $state(true);

	const poliMetaMap = {
		umum: { emoji: '🩺', bg: 'bg-teal-100', text: 'text-teal-700' },
		gigi: { emoji: '🦷', bg: 'bg-sky-100', text: 'text-sky-700' },
		mata: { emoji: '👁️', bg: 'bg-violet-100', text: 'text-violet-700' },
		jantung: { emoji: '❤️', bg: 'bg-rose-100', text: 'text-rose-700' },
		anak: { emoji: '👶', bg: 'bg-amber-100', text: 'text-amber-700' },
		saraf: { emoji: '🧠', bg: 'bg-emerald-100', text: 'text-emerald-700' },
		beda: { emoji: '🦴', bg: 'bg-orange-100', text: 'text-orange-700' },
		kulit: { emoji: '🧴', bg: 'bg-cyan-100', text: 'text-cyan-700' }
	};

	function metaFor(name) {
		const n = (name ?? '').toLowerCase();
		for (const k in poliMetaMap) if (n.includes(k)) return poliMetaMap[k];
		return { emoji: '🏥', bg: 'bg-teal-100', text: 'text-teal-700' };
	}

	onMount(async () => {
		try {
			const res = await api.get(`/polyclinics/${page.params.id}`);
			poli = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="mx-auto max-w-3xl px-4 py-8 md:py-10">
	<a href="/poli" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-teal-700">
		<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
		Kembali ke daftar poli
	</a>

	{#if loading}
		<div class="mt-6 h-96 animate-pulse rounded-2xl bg-slate-100"></div>
	{:else if poli}
		{@const meta = metaFor(poli.name)}
		<div class="mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
			<div class="h-1.5 w-full bg-gradient-to-r from-teal-500 to-cyan-400"></div>
			<div class="p-6 md:p-8">
				<!-- Header -->
				<div class="flex items-start gap-4">
					<div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-2xl shadow-sm ring-1 ring-slate-200 {meta.bg}">
						{meta.emoji}
					</div>
					<div class="flex-1">
						<h1 class="text-xl font-bold text-slate-800 md:text-2xl">{poli.name}</h1>
						<p class="mt-1 text-sm leading-relaxed text-slate-500">{poli.description ?? 'Pelayanan poli dengan dokter spesialis berpengalaman.'}</p>
						<div class="mt-3 flex flex-wrap gap-2">
							<span class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal-200">
								<span class="h-2 w-2 rounded-full bg-teal-500"></span>
								{poli.queue_code ?? 'Poli'} • {poli.doctors?.length ?? poli.doctors_count ?? 0} dokter
							</span>
						</div>
					</div>
				</div>

				<div class="my-6 border-t border-slate-100"></div>

				<!-- Doctors list -->
				<h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
					<span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
					</span>
					Dokter di Poli Ini
				</h2>

				{#if !poli.doctors || poli.doctors.length === 0}
					<p class="mt-3 rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Belum ada dokter terdaftar di poli ini.</p>
				{:else}
					<div class="mt-3 space-y-2">
						{#each poli.doctors as doctor}
							<a
								href="/dokter/{doctor.id}"
								class="group flex items-center gap-4 rounded-xl border border-slate-100 bg-white px-4 py-3.5 shadow-sm transition hover:border-teal-200 hover:bg-teal-50/40"
							>
								<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-100 to-cyan-100 text-sm font-bold text-teal-700 ring-2 ring-teal-50">
									{doctor.user.name.charAt(0).toUpperCase()}
								</div>
								<div class="min-w-0 flex-1">
									<p class="text-sm font-semibold text-slate-800 group-hover:text-teal-700">{doctor.user.name}</p>
									<p class="text-xs text-slate-500">{doctor.specialization ?? 'Dokter Umum'}</p>
								</div>
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-300 group-hover:text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
							</a>
						{/each}
					</div>
				{/if}

				<a href="/dokter" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 py-3 text-sm font-semibold text-white shadow-md shadow-teal-600/20 transition hover:bg-teal-700">
					Lihat Semua Dokter
					<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
				</a>
			</div>
		</div>
	{/if}
</section>
