<script>
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let doctor = $state(null);
	let loading = $state(true);

	const hariLabel = {
		senin: 'Senin', selasa: 'Selasa', rabu: 'Rabu', kamis: 'Kamis',
		jumat: 'Jumat', sabtu: 'Sabtu', minggu: 'Minggu'
	};
	const hariOrder = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];

	function sortedSchedules(schedules) {
		return [...schedules].sort((a,b) => hariOrder.indexOf(a.day) - hariOrder.indexOf(b.day));
	}

	onMount(async () => {
		try {
			const res = await api.get(`/doctors/${page.params.id}`);
			doctor = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="mx-auto max-w-3xl px-4 py-8 md:py-10">
	<a href="/dokter" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-teal-700">
		<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
		Kembali ke daftar dokter
	</a>

	{#if loading}
		<div class="mt-6 h-96 animate-pulse rounded-2xl bg-slate-100"></div>
	{:else if doctor}
		<div class="mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
			<!-- Top accent -->
			<div class="h-1.5 w-full bg-gradient-to-r from-teal-500 to-cyan-400"></div>

			<div class="p-6 md:p-8">
				<!-- Profile header -->
				<div class="flex flex-col items-center gap-5 text-center md:flex-row md:text-left">
					<div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-100 to-cyan-100 text-3xl font-bold text-teal-700 ring-4 ring-teal-50">
						{doctor.user.name.charAt(0).toUpperCase()}
					</div>
					<div class="flex-1">
						<h1 class="text-xl font-bold text-slate-800 md:text-2xl">{doctor.user.name}</h1>
						<p class="mt-1 text-sm font-medium text-teal-700">{doctor.specialization ?? 'Dokter Umum'}</p>
						<p class="mt-1 inline-flex items-center gap-1.5 text-sm text-slate-500">
							<span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-teal-50 text-xs">🏥</span>
							{doctor.polyclinic.name}
						</p>
					</div>
					<div class="hidden md:block">
						<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
							<span class="h-2 w-2 rounded-full bg-emerald-500"></span> Aktif
						</span>
					</div>
				</div>

				<!-- Divider -->
				<div class="my-6 border-t border-slate-100"></div>

				<!-- Jadwal -->
				<div>
					<h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
						<span class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
							<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
						</span>
						Jadwal Praktik
					</h2>

					{#if !doctor.schedules || doctor.schedules.length === 0}
						<p class="mt-3 rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Jadwal belum tersedia.</p>
					{:else}
						<div class="mt-3 space-y-2">
							{#each sortedSchedules(doctor.schedules) as jadwal}
								<div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3 text-sm">
									<span class="font-medium text-slate-700">{hariLabel[jadwal.day] ?? jadwal.day}</span>
									<span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm ring-1 ring-slate-200">
										<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
										{String(jadwal.start_time).slice(0,5)} - {String(jadwal.end_time).slice(0,5)}
									</span>
								</div>
							{/each}
						</div>
					{/if}
				</div>

				<!-- CTA -->
				<a
					href="/register"
					class="mt-8 flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 py-3.5 text-sm font-semibold text-white shadow-md shadow-teal-600/20 transition hover:bg-teal-700"
				>
					Lihat Jadwal
					<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
				</a>
				<p class="mt-3 text-center text-xs text-slate-400">Buat akun untuk daftar kunjungan dengan dokter ini</p>
			</div>
		</div>
	{/if}
</section>
