<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let queues = $state([]);
	let loading = $state(true);
	let error = $state('');
	let filterStatus = $state('semua');

	const statusLabel = { menunggu: 'Menunggu', dipanggil: 'Dipanggil', diproses: 'Diperiksa', selesai: 'Selesai' };
	const statusColor = { menunggu: 'bg-amber-100 text-amber-700', dipanggil: 'bg-blue-100 text-blue-700', diproses: 'bg-teal-100 text-teal-700', selesai: 'bg-gray-100 text-gray-600' };

	onMount(async () => {
		if (!requireRole('admin')) return;
		await load();
		loading = false;
	});

	async function load() {
		error = '';
		try {
			const res = await api.get('/queues/today');
			queues = res.data;
		} catch (e) {
			error = e.message || 'Gagal memuat kunjungan hari ini';
		}
	}

	const filtered = $derived(filterStatus === 'semua' ? queues : queues.filter((q) => q.status === filterStatus));
	const grouped = $derived(
		Object.entries(
			filtered.reduce((acc, q) => {
				const poli = q.visit?.polyclinic?.name ?? 'Tanpa Poli';
				(acc[poli] ??= []).push(q);
				return acc;
			}, {})
		)
	);
</script>

<h1 class="text-xl font-bold text-gray-800">Kunjungan Hari Ini</h1>
<p class="mb-4 text-sm text-gray-500">Daftar antrian dari <code>/queues/today</code> dikelompokkan per poli.</p>

<div class="mb-4 flex flex-wrap items-center gap-2">
	{#each ['semua', 'menunggu', 'dipanggil', 'diproses', 'selesai'] as s}
		<button onclick={() => (filterStatus = s)} class="rounded-full border px-3 py-1.5 text-xs capitalize {filterStatus === s ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-gray-600 hover:bg-gray-50'}">{s}</button>
	{/each}
	<button onclick={load} class="ml-auto text-sm text-teal-700 hover:underline">Muat ulang</button>
</div>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if error}
	<p class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</p>
{:else if queues.length === 0}
	<div class="rounded-xl border bg-white p-8 text-center text-sm text-gray-500 shadow-sm">Belum ada kunjungan/antrian hari ini.</div>
{:else}
	<p class="mb-3 text-xs text-gray-500">Total: {filtered.length} antrian</p>
	{#each grouped as [poli, list]}
		<div class="mb-4 overflow-hidden rounded-xl border bg-white shadow-sm">
			<div class="bg-gray-50 px-4 py-2.5 flex items-center justify-between">
				<p class="text-sm font-semibold text-gray-800">{poli}</p>
				<span class="rounded-full bg-white border px-2 py-0.5 text-xs text-gray-600">{list.length} pasien</span>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full text-left text-sm">
					<thead class="bg-white text-xs text-gray-500">
						<tr><th class="px-4 py-2">No. Antrian</th><th class="px-4 py-2">Pasien</th><th class="px-4 py-2">Dokter</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Jam</th></tr>
					</thead>
					<tbody>
						{#each list as q}
							<tr class="border-t">
								<td class="px-4 py-2.5 font-mono font-medium text-gray-800">{q.queue_number}</td>
								<td class="px-4 py-2.5"><p class="font-medium text-gray-800">{q.visit?.patient?.user?.name ?? '-'}</p><p class="text-xs text-gray-500">{q.visit?.patient?.nik ?? ''}</p></td>
								<td class="px-4 py-2.5">{q.visit?.doctor?.user?.name ?? '-'}</td>
								<td class="px-4 py-2.5"><span class="rounded-full px-2.5 py-1 text-xs font-medium {statusColor[q.status] ?? 'bg-gray-100 text-gray-600'}">{statusLabel[q.status] ?? q.status}</span></td>
								<td class="px-4 py-2.5 text-xs text-gray-500">{q.created_at ? new Date(q.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
	{/each}
{/if}
