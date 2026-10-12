<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';
	import { page } from '$app/state';
	import { goto } from '$app/navigation';

	let search = $state('');
	let searchResults = $state([]);
	let searching = $state(false);
	let selected = $state(null);
	let records = $state([]);
	let loadingRecords = $state(false);
	let error = $state('');
	let searchError = $state('');
	let debounceTimer;

	onMount(async () => {
		if (!requireRole('dokter')) return;
		const pid = page.url.searchParams.get('patient_id');
		if (pid) await loadPatientAndHistory(pid);
	});

	async function loadPatientAndHistory(patientId) {
		error = ''; loadingRecords = true;
		try {
			try {
				const pRes = await api.get('/patients/' + patientId);
				selected = pRes.data ?? pRes;
			} catch {}
			const rRes = await api.get('/patients/' + patientId + '/medical-records');
			records = rRes.data ?? rRes ?? [];
		} catch (err) {
			error = err.message || 'Gagal memuat rekam medis';
		} finally { loadingRecords = false; }
	}

	async function doSearch(q) {
		const term = q.trim();
		if (!term) { searchResults = []; return; }
		searching = true; searchError = '';
		try {
			const res = await api.get('/patients?search=' + encodeURIComponent(term));
			searchResults = res.data ?? res ?? [];
		} catch (err) {
			searchError = err.message || 'Gagal mencari pasien';
		} finally { searching = false; }
	}

	function onInput() {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(() => doSearch(search), 350);
	}

	function onSubmit(e) {
		e.preventDefault();
		clearTimeout(debounceTimer);
		doSearch(search);
	}

	async function selectPatient(p) {
		selected = p;
		searchResults = [];
		search = p.user?.name ?? '';
		const url = new URL(window.location.href);
		url.searchParams.set('patient_id', String(p.id));
		goto(url.pathname + url.search, { replaceState: true, noScroll: true });
		await loadPatientAndHistory(p.id);
	}

	function clearSelection() {
		selected = null; records = []; error = '';
		const url = new URL(window.location.href);
		url.searchParams.delete('patient_id');
		goto(url.pathname + url.search, { replaceState: true, noScroll: true });
		search = ''; searchResults = [];
	}

	function fmtDate(d) {
		if (!d) return '-';
		try { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); } catch { return d; }
	}
</script>

<div class="mx-auto max-w-5xl">
	<h1 class="text-xl font-bold text-slate-800">Rekam Medis</h1>
	<p class="mb-5 text-sm text-slate-500">Cari pasien, lalu lihat riwayat pemeriksaan dan resepnya.</p>

	<form onsubmit={onSubmit} class="relative">
		<div class="flex gap-2">
			<div class="relative flex-1">
				<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><circle cx="11" cy="11" r="7"/><path d="M20 20L16 16"/></svg>
				</span>
				<input
					bind:value={search}
					oninput={onInput}
					placeholder="Cari pasien (nama / email / NIK)..."
					class="w-full rounded-2xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100"
				/>
			</div>
			<button type="submit" class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Cari</button>
			{#if selected}
				<button type="button" onclick={clearSelection} class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</button>
			{/if}
		</div>
		{#if searchResults.length > 0}
			<div class="absolute z-10 mt-2 max-h-72 w-full overflow-auto rounded-2xl border bg-white shadow-lg">
				{#each searchResults as p}
					<button type="button" onclick={() => selectPatient(p)} class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-slate-50">
						<div class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">{p.user?.name?.charAt(0) ?? '?'}</div>
						<div class="min-w-0 flex-1">
							<p class="truncate text-sm font-medium text-slate-800">{p.user?.name ?? '-'}</p>
							<p class="truncate text-xs text-slate-500">{p.user?.email ?? '-'} &middot; {p.nik ?? '-'}</p>
						</div>
						<span class="text-xs text-teal-600">Pilih &rarr;</span>
					</button>
				{/each}
			</div>
		{:else if searching}
			<p class="mt-2 text-xs text-slate-400">Mencari...</p>
		{/if}
		{#if searchError}<p class="mt-2 text-xs text-red-600">{searchError}</p>{/if}
	</form>

	{#if selected}
		<div class="mt-6 rounded-2xl border bg-white p-4 shadow-sm">
			<div class="flex items-center gap-3">
				<div class="flex h-11 w-11 items-center justify-center rounded-full bg-teal-600 font-bold text-white">{selected.user?.name?.charAt(0) ?? '?'}</div>
				<div>
					<p class="text-sm font-semibold text-slate-800">{selected.user?.name ?? '-'}</p>
					<p class="text-xs text-slate-500">{selected.user?.email ?? '-'} &middot; NIK {selected.nik ?? '-'}</p>
				</div>
				<span class="ml-auto rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{selected.gender ?? '-'} &middot; {selected.phone ?? '-'}</span>
			</div>
		</div>
	{/if}

	<div class="mt-6">
		{#if !selected}
			<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
				<p class="text-sm font-medium text-slate-600">Belum memilih pasien</p>
				<p class="mt-1 text-xs text-slate-500">Ketik nama, email, atau NIK di kolom pencarian di atas, lalu pilih pasien untuk melihat riwayat rekam medisnya.</p>
			</div>
		{:else if loadingRecords}
			<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
				<p class="text-sm text-slate-500">Memuat rekam medis...</p>
			</div>
		{:else if error}
			<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>
		{:else if records.length === 0}
			<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
				<p class="text-sm font-medium text-slate-600">Belum ada rekam medis</p>
				<p class="mt-1 text-xs text-slate-500">Pasien ini belum memiliki riwayat pemeriksaan.</p>
			</div>
		{:else}
			<p class="mb-3 text-xs text-slate-400">{records.length} riwayat ditemukan</p>
			<div class="space-y-3">
				{#each records as r}
					<div class="rounded-2xl border bg-white p-5 shadow-sm">
						<div class="flex flex-wrap items-start justify-between gap-2">
							<div>
								<p class="text-xs text-slate-400">{fmtDate(r.created_at)} &middot; {r.visit?.polyclinic?.name ?? '-'} &middot; {r.visit?.doctor?.user?.name ?? '-'}</p>
								<p class="mt-1 text-sm font-semibold text-slate-800">{r.diagnosis}</p>
							</div>
							<span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-teal-200">Visit #{r.visit_id}</span>
						</div>
						<div class="mt-3 grid gap-3 sm:grid-cols-2">
							<div class="rounded-xl bg-slate-50 p-3">
								<p class="text-xs font-medium text-slate-500">Hasil Pemeriksaan</p>
								<p class="mt-1 text-sm text-slate-700">{r.examination_result ?? '-'}</p>
							</div>
							<div class="rounded-xl bg-slate-50 p-3">
								<p class="text-xs font-medium text-slate-500">Tindakan</p>
								<p class="mt-1 text-sm text-slate-700">{r.treatment ?? '-'}</p>
							</div>
						</div>
						{#if r.notes}
							<div class="mt-3 rounded-xl bg-amber-50 p-3 ring-1 ring-amber-100">
								<p class="text-xs font-medium text-amber-700">Catatan</p>
								<p class="mt-1 text-sm text-amber-900">{r.notes}</p>
							</div>
						{/if}
						{#if r.prescription}
							<div class="mt-3 rounded-xl border border-teal-100 bg-teal-50/50 p-3">
								<p class="text-xs font-medium text-teal-700">Resep &middot; {r.prescription.status}</p>
								{#if r.prescription.items?.length}
									<ul class="mt-1 list-disc pl-4 text-sm text-slate-700">
										{#each r.prescription.items as it}
											<li>{it.medicine?.name ?? 'Obat #' + it.medicine_id} &times;{it.quantity} &mdash; {it.dosage}</li>
										{/each}
									</ul>
								{:else}
									<p class="mt-1 text-sm text-slate-500">Tidak ada item resep.</p>
								{/if}
							</div>
						{/if}
					</div>
				{/each}
			</div>
		{/if}
	</div>
</div>
