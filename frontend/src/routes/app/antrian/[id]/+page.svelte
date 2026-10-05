<script>
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let visit = $state(null);
	let loading = $state(true);

	const statusLabel = {
		menunggu: 'Menunggu', dipanggil: 'Dipanggil', diproses: 'Diperiksa', selesai: 'Selesai'
	};

	onMount(async () => {
		try {
			const res = await api.get(`/visits/${page.params.id}/queue`);
			visit = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<h1 class="mb-6 text-xl font-bold text-gray-800">Antrian Saya</h1>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if visit}
	<div class="max-w-md rounded-xl border bg-white p-6 text-center shadow-sm">
		<p class="text-sm text-gray-500">{visit.polyclinic.name}</p>
		<p class="my-3 text-5xl font-bold text-teal-700">{visit.queue?.queue_number ?? '-'}</p>
		<p class="text-sm text-gray-500">
			Status: <span class="font-medium text-gray-800">{statusLabel[visit.queue?.status] ?? '-'}</span>
		</p>
		<div class="mt-4 border-t pt-4 text-left text-sm text-gray-600">
			<p>Dokter: {visit.doctor.user.name}</p>
			<p>Tanggal: {new Date(visit.visit_date).toLocaleDateString('id-ID', {day : 'numeric', month : 'long', year : 'numeric'})}</p>
			<p>Keluhan: {visit.complaint}</p>
		</div>
	</div>
{/if}