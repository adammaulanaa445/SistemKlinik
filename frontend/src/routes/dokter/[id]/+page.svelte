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

	onMount(async () => {
		try {
			const res = await api.get(`/doctors/${page.params.id}`);
			doctor = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="mx-auto max-w-3xl px-4 py-10">
	{#if loading}
		<p class="text-sm text-gray-500">Memuat...</p>
	{:else if doctor}
		<div class="rounded-xl border bg-white p-6 shadow-sm">
			<div class="flex items-center gap-4">
				<div
					class="flex h-16 w-16 items-center justify-center rounded-full bg-teal-100 text-xl font-semibold text-teal-700"
				>
					{doctor.user.name.charAt(0)}
				</div>
				<div>
					<h1 class="text-xl font-bold text-gray-800">{doctor.user.name}</h1>
					<p class="text-sm text-gray-500">{doctor.specialization} &middot; {doctor.polyclinic.name}</p>
				</div>
			</div>

			<h2 class="mt-6 mb-2 font-semibold text-gray-700">Jadwal Praktik</h2>
			{#if doctor.schedules.length === 0}
				<p class="text-sm text-gray-500">Jadwal belum tersedia.</p>
			{:else}
				<div class="space-y-2">
					{#each doctor.schedules as jadwal}
						<div class="flex justify-between rounded-lg bg-gray-50 px-4 py-2 text-sm">
							<span>{hariLabel[jadwal.day]}</span>
							<span class="text-gray-500">{jadwal.start_time.slice(0, 5)} - {jadwal.end_time.slice(0, 5)}</span>
						</div>
					{/each}
				</div>
			{/if}

			<a
				href="/register"
				class="mt-6 block w-full rounded-lg bg-teal-600 py-2.5 text-center text-white hover:bg-teal-700"
			>
				Daftar Kunjungan
			</a>
		</div>
	{/if}
</section>