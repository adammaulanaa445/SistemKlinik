<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let polyclinics = $state([]);
	let loading = $state(true);

	onMount(async () => {
		try {
			const res = await api.get('/polyclinics');
			polyclinics = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<section class="mx-auto max-w-6xl px-4 py-10">
	<h1 class="text-2xl font-bold text-gray-800">Poli Kami</h1>

	{#if loading}
		<p class="mt-4 text-sm text-gray-500">Memuat...</p>
	{:else}
		<div class="mt-6 grid gap-4 sm:grid-cols-2 md:grid-cols-4">
			{#each polyclinics as poli}
				<a
					href="/poli/{poli.id}"
					class="rounded-xl border bg-white p-5 text-center shadow-sm hover:border-teal-300"
				>
					<div
						class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-xl font-semibold text-teal-700"
					>
						{poli.queue_code}
					</div>
					<p class="font-medium text-gray-700">{poli.name}</p>
					<p class="mt-1 text-xs text-gray-400">{poli.doctors_count} dokter</p>
				</a>
			{/each}
		</div>
	{/if}
</section>