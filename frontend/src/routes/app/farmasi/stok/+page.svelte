<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let medicines = $state([]);
	let filtered = $state([]);
	let loading = $state(true);
	let error = $state('');
	let success = $state('');
	let search = $state('');
	let statusFilter = $state('semua');
	let stockDraft = $state({});
	let working = $state(null);
	let showAdd = $state(false);
	let form = $state({ code: '', name: '', unit: '', stock: 0, price: 0, category: '' });
	let formErrors = $state({});
	let submitting = $state(false);

	onMount(async () => {
		if (!requireRole('farmasi', 'admin')) return;
		await load();
		loading = false;
	});

	async function load() {
		error = '';
		const q = statusFilter === 'aktif' ? '' : statusFilter === 'nonaktif' ? '?status=nonaktif' : '?all=1';
		const s = search.trim() ? (q ? '&' : '?') + 'search=' + encodeURIComponent(search.trim()) : '';
		const url = '/medicines' + q + s;
		try {
			const res = await api.get(url);
			medicines = res.data ?? res ?? [];
			filter();
			for (const m of medicines) if (stockDraft[m.id] === undefined) stockDraft[m.id] = m.stock;
		} catch (e) {
			error = e.message || 'Gagal memuat obat';
		}
	}

	function filter() {
		// client fallback if server all filter not supported — already filtered by API
		filtered = medicines;
	}

	function onSearchInput() { load(); }
	function onStatusChange() { load(); }

	async function updateStock(id) {
		const val = Number(stockDraft[id]);
		if (!Number.isInteger(val) || val < 0) { error = 'Stok harus bilangan bulat >= 0'; return; }
		working = 'stock' + id; error = ''; success = '';
		try {
			await api.patch('/medicines/' + id + '/stock', { stock: val });
			success = 'Stok diperbarui';
			await load();
		} catch (e) {
			error = e.message || 'Gagal memperbarui stok';
			if (e.errors) error += ' ' + Object.values(e.errors).flat().join(' ');
		} finally { working = null; }
	}

	async function toggleActive(m) {
		working = 'toggle' + m.id; error = ''; success = '';
		try {
			if (m.status === 'aktif') {
				await api.delete('/medicines/' + m.id);
				success = m.name + ' dinonaktifkan';
			} else {
				await api.patch('/medicines/' + m.id + '/activate', {});
				success = m.name + ' diaktifkan';
			}
			await load();
		} catch (e) {
			error = e.message || 'Gagal mengubah status';
		} finally { working = null; }
	}

	async function addMedicine(e) {
		e.preventDefault();
		formErrors = {}; error = ''; success = ''; submitting = true;
		try {
			await api.post('/medicines', {
				code: form.code.trim(),
				name: form.name.trim(),
				unit: form.unit.trim(),
				stock: Number(form.stock),
				price: Number(form.price),
				category: form.category.trim() || null
			});
			success = 'Obat berhasil ditambahkan';
			form = { code: '', name: '', unit: '', stock: 0, price: 0, category: '' };
			showAdd = false;
			await load();
		} catch (err) {
			formErrors = err.errors ?? {};
			error = err.errors ? '' : err.message || 'Gagal menambah obat';
		} finally { submitting = false; }
	}
</script>

<div class="mx-auto max-w-5xl">
	<h1 class="text-xl font-bold text-slate-800">Stok Obat — Farmasi</h1>
	<p class="mb-5 text-sm text-slate-500">Kelola master obat, stok, harga, dan status aktif.</p>

	{#if error}<p class="mb-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-200">{error}</p>{/if}
	{#if success}<p class="mb-3 rounded-xl bg-teal-50 px-3 py-2 text-sm text-teal-700 ring-1 ring-teal-200">{success}</p>{/if}

	<div class="mb-4 flex flex-wrap items-center gap-2">
		<input bind:value={search} oninput={onSearchInput} placeholder="Cari kode / nama obat..." class="min-w-[220px] flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none sm:max-w-xs" />
		<select bind:value={statusFilter} onchange={onStatusChange} class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
			<option value="semua">Semua</option>
			<option value="aktif">Aktif</option>
			<option value="nonaktif">Nonaktif</option>
		</select>
		<button onclick={load} class="rounded-xl border px-3 py-2.5 text-sm hover:bg-slate-50">Muat ulang</button>
		<button onclick={() => (showAdd = !showAdd)} class="ml-auto rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">{showAdd ? 'Tutup' : '+ Tambah Obat'}</button>
	</div>

	{#if showAdd}
		<form onsubmit={addMedicine} class="mb-6 grid gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:grid-cols-3">
			<div><label class="mb-1 block text-xs font-medium text-slate-600" for="code">Kode *</label><input id="code" bind:value={form.code} required placeholder="OBT001" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />{#if formErrors.code}<p class="mt-1 text-xs text-red-600">{formErrors.code[0]}</p>{/if}</div>
			<div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600" for="name">Nama *</label><input id="name" bind:value={form.name} required placeholder="Paracetamol 500mg" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />{#if formErrors.name}<p class="mt-1 text-xs text-red-600">{formErrors.name[0]}</p>{/if}</div>
			<div><label class="mb-1 block text-xs font-medium text-slate-600" for="unit">Satuan *</label><input id="unit" bind:value={form.unit} required placeholder="tablet / botol / strip" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />{#if formErrors.unit}<p class="mt-1 text-xs text-red-600">{formErrors.unit[0]}</p>{/if}</div>
			<div><label class="mb-1 block text-xs font-medium text-slate-600" for="stock">Stok *</label><input id="stock" type="number" min="0" step="1" bind:value={form.stock} required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />{#if formErrors.stock}<p class="mt-1 text-xs text-red-600">{formErrors.stock[0]}</p>{/if}</div>
			<div><label class="mb-1 block text-xs font-medium text-slate-600" for="price">Harga *</label><input id="price" type="number" min="0" step="0.01" bind:value={form.price} required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />{#if formErrors.price}<p class="mt-1 text-xs text-red-600">{formErrors.price[0]}</p>{/if}</div>
			<div class="sm:col-span-3"><label class="mb-1 block text-xs font-medium text-slate-600" for="category">Kategori</label><input id="category" bind:value={form.category} placeholder="Analgesik / Antibiotik / Vitamin" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" /></div>
			<div class="sm:col-span-3"><button type="submit" disabled={submitting} class="w-full rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50">{submitting ? 'Menyimpan...' : 'Simpan Obat'}</button></div>
		</form>
	{/if}

	{#if loading}
		<p class="rounded-xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if filtered.length === 0}
		<div class="rounded-xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Belum ada obat. Tambahkan obat baru di atas.</div>
	{:else}
		<div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="bg-slate-50 text-xs text-slate-500">
						<tr>
							<th class="px-3 py-2.5 text-left font-medium">Kode</th>
							<th class="px-3 py-2.5 text-left font-medium">Nama / Kategori</th>
							<th class="px-3 py-2.5 text-left font-medium">Stok</th>
							<th class="px-3 py-2.5 text-left font-medium">Harga</th>
							<th class="px-3 py-2.5 text-left font-medium">Status</th>
							<th class="px-3 py-2.5 text-right font-medium">Aksi</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						{#each filtered as m}
							<tr class="hover:bg-slate-50 {m.stock < 20 ? 'bg-amber-50/40' : ''}">
								<td class="px-3 py-2.5 font-mono text-xs font-medium text-slate-800">{m.code}</td>
								<td class="px-3 py-2.5">
									<p class="font-medium text-slate-800">{m.name}</p>
									<p class="text-xs text-slate-500">{m.category ?? '-'} · {m.unit}</p>
								</td>
								<td class="px-3 py-2.5">
									<div class="flex items-center gap-1">
										<input type="number" min="0" step="1" bind:value={stockDraft[m.id]} class="w-20 rounded-lg border border-slate-200 px-2 py-1.5 text-sm" />
										<span class="text-xs text-slate-500">{m.unit}</span>
										<button onclick={() => updateStock(m.id)} disabled={working === 'stock' + m.id || Number(stockDraft[m.id]) === m.stock} class="rounded-lg bg-teal-600 px-2 py-1.5 text-xs font-medium text-white hover:bg-teal-700 disabled:opacity-40">Simpan</button>
									</div>
									{#if m.stock < 20}<span class="text-[11px] font-medium text-amber-600">Stok menipis</span>{/if}
								</td>
								<td class="px-3 py-2.5 text-xs font-medium text-slate-700">Rp {Number(m.price).toLocaleString('id-ID')}</td>
								<td class="px-3 py-2.5"><span class="rounded-full px-2 py-1 text-xs font-medium {m.status === 'aktif' ? 'bg-teal-50 text-teal-700 ring-1 ring-teal-200' : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'}">{m.status}</span></td>
								<td class="px-3 py-2.5 text-right">
									<button onclick={() => toggleActive(m)} disabled={working === 'toggle' + m.id} class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-slate-50 disabled:opacity-50">
										{m.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'}
									</button>
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
</div>
