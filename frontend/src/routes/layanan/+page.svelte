<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let polyclinics = $state([]);
	let loading = $state(true);

	const layananUtama = [
		{ nama: 'Pemeriksaan Umum', ikon: '🩺', bg: 'bg-teal-50', color: 'text-teal-600' },
		{ nama: 'Konsultasi Dokter', ikon: '👨‍⚕️', bg: 'bg-blue-50', color: 'text-blue-600' },
		{ nama: 'Laboratorium', ikon: '🧪', bg: 'bg-amber-50', color: 'text-amber-600' },
		{ nama: 'Farmasi', ikon: '💊', bg: 'bg-emerald-50', color: 'text-emerald-600' },
		{ nama: 'Rekam Medis', ikon: '📋', bg: 'bg-violet-50', color: 'text-violet-600' },
		{ nama: 'Jadwal Dokter', ikon: '📅', bg: 'bg-orange-50', color: 'text-orange-600' }
	];

	const poliIcons = ['🩺','🦷','👁️','🧠','❤️','👶','🦴','🧴'];
	const poliColors = [
		'bg-teal-100 text-teal-700',
		'bg-blue-100 text-blue-700',
		'bg-violet-100 text-violet-700',
		'bg-amber-100 text-amber-700',
		'bg-rose-100 text-rose-700',
		'bg-emerald-100 text-emerald-700',
		'bg-orange-100 text-orange-700',
		'bg-cyan-100 text-cyan-700'
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

<!-- Hero teal dark -->
<section class="bg-[#0f3d3e] px-4 py-14 text-center text-white md:py-16">
	<h1 class="text-2xl font-bold tracking-tight md:text-3xl">Layanan Kesehatan Lengkap</h1>
	<p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-teal-100/90 md:text-[15px]">
		Kami menyediakan berbagai layanan kesehatan untuk kebutuhan Anda dan keluarga dengan tenaga profesional dan fasilitas modern.
	</p>
</section>

<section class="mx-auto max-w-6xl px-4 py-10 md:py-12">
	<!-- Layanan Kami - 6 cards -->
	<h2 class="text-lg font-bold text-slate-800">Layanan Kami</h2>
	<p class="mt-1 text-sm text-slate-500">Pilih layanan yang Anda butuhkan</p>
	<div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
		{#each layananUtama as layanan}
			<div class="rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm transition hover:shadow-md">
				<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {layanan.bg} text-2xl shadow-sm">
					{layanan.ikon}
				</div>
				<p class="mt-3 text-sm font-semibold text-slate-700">{layanan.nama}</p>
			</div>
		{/each}
	</div>

	<!-- Poli Kami -->
	<div class="mt-12">
		<div class="flex items-center justify-between">
			<div>
				<h2 class="text-lg font-bold text-slate-800">Poli Kami</h2>
				<p class="mt-1 text-sm text-slate-500">Tersedia berbagai poli spesialis</p>
			</div>
			<a href="/poli" class="hidden text-sm font-semibold text-teal-600 hover:text-teal-700 md:block">Lihat semua →</a>
		</div>

		{#if loading}
			<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
				{#each Array(6) as _}
					<div class="h-28 animate-pulse rounded-2xl bg-slate-100"></div>
				{/each}
			</div>
		{:else if polyclinics.length === 0}
			<p class="mt-6 rounded-2xl border border-dashed bg-white p-8 text-center text-sm text-slate-500">Belum ada data poli.</p>
		{:else}
			<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
				{#each polyclinics as poli, i}
					<a href="/poli/{poli.id}" class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:border-teal-200 hover:shadow-md">
						<div class="flex items-start gap-4">
							<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-lg font-bold shadow-sm {poliColors[i % poliColors.length]}">
								{poliIcons[i % poliIcons.length]}
							</div>
							<div class="min-w-0 flex-1">
								<h3 class="font-semibold text-slate-800 group-hover:text-teal-700">{poli.name}</h3>
								<p class="mt-1 line-clamp-2 text-sm leading-relaxed text-slate-500">{poli.description ?? 'Pelayanan poli spesialis dengan dokter berpengalaman.'}</p>
								<p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">
									<span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
									{poli.doctors_count ?? 0} dokter tersedia
								</p>
							</div>
						</div>
					</a>
				{/each}
			</div>
			<a href="/poli" class="mt-6 flex justify-center text-sm font-semibold text-teal-600 hover:text-teal-700 md:hidden">Lihat semua poli →</a>
		{/if}
	</div>
</section>
