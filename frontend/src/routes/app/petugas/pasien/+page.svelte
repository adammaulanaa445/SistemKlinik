<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let patients = $state([]);
	let filtered = $state([]);
	let query = $state('');
	let selected = $state(null);
	let loading = $state(true);
	let error = $state('');
	let detailLoading = $state(false);

	onMount(async () => {
		if (!requireRole('petugas', 'admin')) return;
		try {
			const res = await api.get('/patients');
			patients = res.data ?? res ?? [];
			filtered = patients;
		} catch (e) {
			error = e.message || 'Gagal memuat pasien';
		} finally { loading = false; }
	});

	function onSearch() {
		const q = query.trim().toLowerCase();
		if (!q) { filtered = patients; return; }
		filtered = patients.filter(p =>
			(p.user?.name ?? '').toLowerCase().includes(q) ||
			(p.nik ?? '').toLowerCase().includes(q) ||
			(p.user?.email ?? '').toLowerCase().includes(q) ||
			(p.phone ?? '').includes(q)
		);
	}

	async function openDetail(p) {
		selected = p;
		detailLoading = true;
		try {
			const res = await api.get('/patients/' + p.id);
			selected = res.data ?? res;
		} catch (e) {
			error = e.message || 'Gagal memuat detail pasien';
		} finally { detailLoading = false; }
	}
</script>

<div class="mx-auto max-w-5xl">
	<h1 class="text-xl font-bold text-slate-800">Data Pasien</h1>
	<p class="mb-5 text-sm text-slate-500">Cari dan lihat data pasien terdaftar.</p>

	<div class="mb-4 flex items-center gap-3">
		<input bind:value={query} oninput={onSearch} placeholder="Cari nama / NIK / email / telepon..." class="w-full max-w-md rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none" />
		<span class="text-xs text-slate-500">{filtered.length} pasien</span>
	</div>

	{#if error}<p class="mb-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-200">{error}</p>{/if}

	{#if loading}
		<p class="rounded-xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if filtered.length === 0}
		<div class="rounded-xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Tidak ada pasien ditemukan.</div>
	{:else}
		<div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="bg-slate-50 text-xs text-slate-500">
						<tr>
							<th class="px-4 py-2.5 text-left font-medium">Pasien</th>
							<th class="px-4 py-2.5 text-left font-medium">NIK</th>
							<th class="px-4 py-2.5 text-left font-medium">Kontak</th>
							<th class="px-4 py-2.5 text-left font-medium">JK / Lahir</th>
							<th class="px-4 py-2.5 text-right font-medium"></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						{#each filtered as p}
							<tr class="hover:bg-slate-50">
								<td class="px-4 py-3">
									<div class="flex items-center gap-2">
										<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">{p.user?.name?.charAt(0) ?? '?'}</div>
										<div>
											<p class="font-medium text-slate-800">{p.user?.name ?? '-'}</p>
											<p class="text-xs text-slate-500">{p.user?.email ?? '-'}</p>
										</div>
									</div>
								</td>
								<td class="px-4 py-3 font-mono text-xs text-slate-700">{p.nik ?? '-'}</td>
								<td class="px-4 py-3 text-xs text-slate-600">{p.phone ?? '-'}<br /><span class="text-[11px] text-slate-400">{p.address ? p.address.slice(0,30) + (p.address.length>30?'…':'') : '-'}</span></td>
								<td class="px-4 py-3 text-xs text-slate-600">{p.gender ?? '-'} · {p.birth_date ? String(p.birth_date).slice(0,10) : '-'}</td>
								<td class="px-4 py-3 text-right">
									<button onclick={() => openDetail(p)} class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-slate-50">Detail</button>
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
	{/if}

	{#if selected}
		<div class="fixed inset-0 z-40 flex items-center justify-center bg-black/30 p-4" onclick={() => selected = null} role="presentation">
			<!-- svelte-ignore a11y_click_events_have_key_events -->
			<div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true">
				<div class="flex items-start justify-between">
					<div>
						<p class="text-sm font-bold text-slate-800">{selected.user?.name ?? '-'}</p>
						<p class="text-xs text-slate-500">{selected.user?.email ?? '-'}</p>
					</div>
					<button onclick={() => selected = null} class="rounded-lg bg-slate-100 px-2 py-1 text-sm">✕</button>
				</div>
				{#if detailLoading}
					<p class="mt-4 text-sm text-slate-500">Memuat detail...</p>
				{:else}
					<div class="mt-4 grid gap-2 rounded-xl bg-slate-50 p-3 text-sm">
						<p><span class="text-slate-500">NIK:</span> <span class="font-mono font-medium text-slate-800">{selected.nik ?? '-'}</span></p>
						<p><span class="text-slate-500">JK:</span> {selected.gender ?? '-'} · <span class="text-slate-500">Lahir:</span> {selected.birth_date ? String(selected.birth_date).slice(0,10) : '-'}</p>
						<p><span class="text-slate-500">Telepon:</span> {selected.phone ?? '-'}</p>
						<p><span class="text-slate-500">Alamat:</span> {selected.address ?? '-'}</p>
						{#if selected.visits_count !== undefined}<p><span class="text-slate-500">Total kunjungan:</span> {selected.visits_count}</p>{/if}
					</div>
				{/if}
			</div>
		</div>
	{/if}
</div>
