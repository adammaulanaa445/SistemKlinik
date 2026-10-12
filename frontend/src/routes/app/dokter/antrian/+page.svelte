<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let queues = $state([]);
	let loading = $state(true);
	let error = $state('');
	let filter = $state('all');

	const statusLabel = { menunggu: 'Menunggu', dipanggil: 'Dipanggil', diproses: 'Diperiksa', selesai: 'Selesai' };
	const statusColor = {
		menunggu: 'bg-amber-100 text-amber-700 ring-amber-200',
		dipanggil: 'bg-blue-100 text-blue-700 ring-blue-200',
		diproses: 'bg-teal-100 text-teal-700 ring-teal-200',
		selesai: 'bg-slate-100 text-slate-600 ring-slate-200'
	};

	const filtered = $derived(filter === 'all' ? queues : queues.filter((q) => q.status === filter));

	onMount(async () => {
		if (!requireRole('dokter')) return;
		try {
			const res = await api.get('/queues/today');
			queues = res.data ?? res ?? [];
		} catch (e) {
			error = e.message || 'Gagal memuat antrian';
		} finally {
			loading = false;
		}
	});

	async function reload() {
		loading = true;
		try {
			const res = await api.get('/queues/today');
			queues = res.data ?? res ?? [];
		} catch (e) {
			error = e.message || 'Gagal memuat';
		} finally {
			loading = false;
		}
	}
</script>

<div class="mx-auto max-w-5xl">
	<h1 class="text-xl font-bold text-slate-800">Antrian Saya Hari Ini</h1>
	<p class="mb-4 text-sm text-slate-500">Hanya menampilkan pasien Anda — difilter otomatis oleh sistem (doctor_id).</p>

	<div class="mb-4 flex flex-wrap items-center gap-2">
		{#each [{ key: 'all', label: 'Semua' }, { key: 'menunggu', label: 'Menunggu' }, { key: 'dipanggil', label: 'Dipanggil' }, { key: 'diproses', label: 'Diperiksa' }, { key: 'selesai', label: 'Selesai' }] as t}
			<button
				onclick={() => (filter = t.key)}
				class="rounded-full px-3 py-1.5 text-xs font-medium ring-1 {filter === t.key
					? 'bg-teal-600 text-white ring-teal-600'
					: 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'}"
			>
				{t.label}
				{#if t.key !== 'all'} ({queues.filter((q) => q.status === t.key).length}){:else}
					({queues.length}){/if}
			</button>
		{/each}
		<button onclick={reload} class="ml-auto text-sm font-medium text-teal-700 hover:underline">Muat ulang</button>
	</div>

	{#if loading}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat antrian...</p>
	{:else if error}
		<p class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</p>
	{:else if filtered.length === 0}
		<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
			<p class="text-sm font-medium text-slate-700">Tidak ada antrian</p>
			<p class="mt-1 text-xs text-slate-500">Filter "{filter}" tidak ada data hari ini.</p>
		</div>
	{:else}
		<div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
			<div class="overflow-x-auto">
				<table class="w-full text-left text-sm">
					<thead class="border-b bg-slate-50 text-xs font-medium text-slate-500">
						<tr>
							<th class="px-4 py-3">No. Antrian</th>
							<th class="px-4 py-3">Pasien</th>
							<th class="px-4 py-3">Poli</th>
							<th class="px-4 py-3">Status</th>
							<th class="px-4 py-3 text-right">Aksi</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						{#each filtered as q}
							<tr class="hover:bg-slate-50/60">
								<td class="px-4 py-3 font-mono font-bold text-teal-700">{q.queue_number}</td>
								<td class="px-4 py-3">
									<p class="font-medium text-slate-800">{q.visit?.patient?.user?.name ?? '-'}</p>
									<p class="text-xs text-slate-500">{q.visit?.patient?.nik ?? '-'} · {q.visit?.complaint?.slice(0, 30) ?? ''}</p>
								</td>
								<td class="px-4 py-3 text-xs text-slate-600">{q.visit?.polyclinic?.name ?? '-'}</td>
								<td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 {statusColor[q.status] ?? ''}">{statusLabel[q.status] ?? q.status}</span></td>
								<td class="px-4 py-3 text-right">
									<a href="/app/pemeriksaan/{q.visit_id}" class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700">Periksa</a>
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
</div>
