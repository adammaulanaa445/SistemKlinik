<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { auth, requireRole } from '$lib/auth.svelte.js';

	let summary = $state(null);
	let loading = $state(true);

	onMount(async () => {
		if (!requireRole('dokter')) return;
		try {
			const res = await api.get('/dashboard/doctor');
			summary = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<h1 class="mb-1 text-xl font-bold text-gray-800">Selamat pagi, {auth.user?.name}</h1>
<p class="mb-6 text-sm text-gray-500">Ringkasan pasien Anda hari ini.</p>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if summary}
	<div class="grid grid-cols-3 gap-4">
		<div class="rounded-xl border bg-white p-4 text-center shadow-sm">
			<p class="text-2xl font-bold text-gray-800">{summary.total_hari_ini}</p>
			<p class="text-xs text-gray-500">Kunjungan</p>
		</div>
		<div class="rounded-xl border bg-white p-4 text-center shadow-sm">
			<p class="text-2xl font-bold text-amber-600">{summary.menunggu}</p>
			<p class="text-xs text-gray-500">Menunggu</p>
		</div>
		<div class="rounded-xl border bg-white p-4 text-center shadow-sm">
			<p class="text-2xl font-bold text-teal-600">{summary.selesai}</p>
			<p class="text-xs text-gray-500">Selesai</p>
		</div>
	</div>

	<h2 class="mt-8 mb-3 font-semibold text-gray-700">Antrian Saya Hari Ini</h2>
	{#if summary.antrian_terbaru.length === 0}
		<p class="text-sm text-gray-500">Belum ada antrian menunggu.</p>
	{:else}
		<div class="space-y-2">
			{#each summary.antrian_terbaru as q}
				<div class="flex items-center justify-between rounded-lg border bg-white px-4 py-3">
					<div>
						<p class="font-medium text-gray-800">{q.queue_number}</p>
						<p class="text-xs text-gray-500">{q.visit.patient.user.name}</p>
					</div>
					<a
						href="/app/pemeriksaan/{q.visit_id}"
						class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm text-white hover:bg-teal-700"
					>
						Lihat Detail
					</a>
				</div>
			{/each}
		</div>
	{/if}
{/if}