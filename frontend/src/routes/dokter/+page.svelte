<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let doctors = $state([]);
	let loading = $state(true);

	onMount(async () => {
		try {
			const res = await api.get('/doctors');
			doctors = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="mx-auto max-w-6xl px-4 py-10">
	<h1 class="text-2xl font-bold text-gray-800">Dokter Kami</h1>

	{#if loading}
		<p class="mt-4 text-sm text-gray-500">Memuat data dokter...</p>
	{:else}
		<div class="mt-6 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
			{#each doctors as doctor}
				<div class="rounded-xl border bg-white p-5 shadow-sm">
					<div class="flex items-center gap-3">
						<div
							class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 font-semibold text-teal-700"
						>
							{doctor.user.name.charAt(0)}
						</div>
						<div>
							<p class="font-semibold text-gray-800">{doctor.user.name}</p>
							<p class="text-sm text-gray-500">{doctor.specialization}</p>
						</div>
					</div>
					<p class="mt-3 text-xs text-gray-400">{doctor.polyclinic.name}</p>
					<a
						href="/dokter/{doctor.id}"
						class="mt-3 block w-full rounded-lg bg-teal-600 py-2 text-center text-sm text-white hover:bg-teal-700"
					>
						Lihat Detail
					</a>
				</div>
			{/each}
		</div>
	{/if}
</section>