<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let visits = $state([]);
	let loading = $state(true);

	const statusLabel = {
		menunggu: 'Menunggu',
		dipanggil: 'Dipanggil',
		diproses: 'Diperiksa',
		selesai: 'Selesai'
	};

	onMount(async () => {
		if (!requireRole('pasien')) return;
		try {
			const res = await api.get('/visits/my');
			visits = res.data.filter((v) => v.status !== 'selesai' && v.status !== 'batal');
		} finally {
			loading = false;
		}
	});
</script>

<div class="mx-auto max-w-3xl">
	<h1 class="text-xl font-bold tracking-tight text-slate-900">Antrian Saya</h1>
	<p class="mb-5 text-sm text-slate-500">Pantau antrian Anda secara real-time.</p>

	{#if loading}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if visits.length === 0}
		<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
			<div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 ring-1 ring-teal-100">🔢</div>
			<p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada antrian aktif</p>
			<p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">Daftar kunjungan terlebih dahulu untuk mendapat nomor antrian.</p>
			<a href="/app/kunjungan" class="mt-4 inline-flex rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Daftar kunjungan</a>
		</div>
	{:else}
		<div class="space-y-4">
			{#each visits as v}
				<a href="/app/antrian/{v.id}" class="block rounded-2xl border bg-white p-5 shadow-sm transition hover:border-teal-200 hover:shadow">
					<div class="flex items-start justify-between">
						<div>
							<p class="text-xs font-medium tracking-wide text-teal-600">{v.polyclinic.name}</p>
							<p class="mt-1 text-3xl font-extrabold tracking-tight text-teal-600">{v.queue?.queue_number ?? '-'}</p>
							<p class="mt-1 text-xs text-slate-500">Status <span class="font-semibold text-slate-700">{statusLabel[v.queue?.status] ?? '-'}</span> · {v.doctor.user.name}</p>
						</div>
						<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">{statusLabel[v.queue?.status] ?? v.status}</span>
					</div>
					<div class="mt-4 flex items-center gap-2 text-xs text-slate-400">
						<span>Ketuk untuk detail</span><span>›</span>
					</div>
				</a>
			{/each}
		</div>
	{/if}
</div>
