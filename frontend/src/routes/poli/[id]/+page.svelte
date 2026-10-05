<script>
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let poli = $state(null);
	let loading = $state(true);

	onMount(async () => {
		try {
			const res = await api.get(`/polyclinics/${page.params.id}`);
			poli = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="mx-auto max-w-3xl px-4 py-10">
	{#if loading}
		<p class="text-sm text-gray-500">Memuat...</p>
	{:else if poli}
		<div class="rounded-xl border bg-white p-6 shadow-sm">
			<div class="flex items-center gap-3">
				<div
					class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-lg font-semibold text-teal-700"
				>
					{poli.queue_code}
				</div>
				<div>
					<h1 class="text-xl font-bold text-gray-800">{poli.name}</h1>
					<p class="text-sm text-gray-500">{poli.description ?? '-'}</p>
				</div>
			</div>

			<h2 class="mt-6 mb-3 font-semibold text-gray-700">Dokter di Poli Ini</h2>
			{#if poli.doctors.length === 0}
				<p class="text-sm text-gray-500">Belum ada dokter terdaftar.</p>
			{:else}
				<div class="space-y-2">
					{#each poli.doctors as doctor}
						<a
							href="/dokter/{doctor.id}"
							class="flex items-center justify-between rounded-lg border px-4 py-3 text-sm hover:bg-gray-50"
						>
							<span>{doctor.user.name}</span>
							<span class="text-gray-400">{doctor.specialization}</span>
						</a>
					{/each}
				</div>
			{/if}
		</div>
	{/if}
</section>