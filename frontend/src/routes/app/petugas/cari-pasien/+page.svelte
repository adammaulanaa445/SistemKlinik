<script>
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let patients = $state([]);
	let loading = $state(true);
	let error = $state('');
	let search = $state('');
	let debounceTimer;

	onMount(async () => {
		if (!requireRole('petugas', 'admin')) return;
		await fetchPatients('');
		loading = false;
	});

	async function fetchPatients(q) {
		error = '';
		try {
			const res = await api.get('/patients' + (q ? '?search=' + encodeURIComponent(q) : ''));
			patients = res.data ?? res ?? [];
		} catch (err) {
			error = err.message || 'Gagal memuat pasien';
		}
	}

	function onSearchInput() {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(() => fetchPatients(search.trim()), 350);
	}

	function onSearchSubmit(e) {
		e.preventDefault();
		clearTimeout(debounceTimer);
		fetchPatients(search.trim());
	}

	function pilihPasien(p) {
		// Simpan pilihan ke sessionStorage agar Pendaftaran bisa pakai langsung (include Cari Data Pasien)
		try {
			sessionStorage.setItem('cari_pasien_selected', JSON.stringify({ id: p.id, name: p.user?.name, nik: p.nik }));
		} catch {}
		goto('/app/petugas/pendaftaran?patient_id=' + p.id);
	}
</script>

<div class="mx-auto max-w-5xl">
	<h1 class="text-xl font-bold text-slate-800">Cari Data Pasien</h1>
	<p class="mb-1 text-sm text-slate-500">
		Gunakan untuk <span class="font-medium text-slate-700">Pendaftaran Kunjungan</span> — data pasien dipakai ulang (input sekali). Cari NIK/nama lalu “Pilih & Daftar”.
	</p>
	<p class="mb-4 text-xs text-slate-400">Use case: <span class="font-mono">Pendaftaran Kunjungan &lt;&lt;include&gt;&gt; Cari Data Pasien</span> · D2 Data Pasien</p>

	<form onsubmit={onSearchSubmit} class="mb-4 flex gap-2">
		<div class="relative flex-1">
			<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><circle cx="11" cy="11" r="7" /><path d="M20 20L16 16" /></svg>
			</span>
			<input
				bind:value={search}
				oninput={onSearchInput}
				placeholder="Cari NIK, nama, atau email..."
				class="w-full rounded-2xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100"
			/>
		</div>
		<button type="submit" class="rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Cari</button>
		<a href="/app/petugas/pendaftaran" class="rounded-2xl border bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Ke Pendaftaran</a>
	</form>

	{#if loading}
		<div class="rounded-2xl border bg-white p-8 text-center shadow-sm"><p class="text-sm text-slate-500">Memuat pasien...</p></div>
	{:else if error}
		<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>
	{:else if patients.length === 0}
		<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
			<p class="text-sm font-medium text-slate-600">Tidak ada pasien</p>
			<p class="mt-1 text-xs text-slate-500">Pasien baru daftar via <a href="/register" class="text-teal-700 hover:underline">Registrasi</a> lalu NIK diisi sekali saat kunjungan pertama.</p>
		</div>
	{:else}
		<div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
			<div class="overflow-x-auto">
				<table class="w-full text-left text-sm">
					<thead class="border-b bg-slate-50 text-xs font-medium text-slate-500">
						<tr>
							<th class="px-4 py-3">Pasien</th>
							<th class="px-4 py-3">NIK</th>
							<th class="px-4 py-3">Kontak</th>
							<th class="px-4 py-3">Data</th>
							<th class="px-4 py-3 text-right">Aksi</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-100">
						{#each patients as p}
							{@const lengkap = !!(p.nik && p.gender && p.birth_date && p.address)}
							<tr class="hover:bg-slate-50/60">
								<td class="px-4 py-3">
									<div class="flex items-center gap-3">
										<div class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">{p.user?.name?.charAt(0) ?? '?'}</div>
										<div>
											<p class="font-medium text-slate-800">{p.user?.name ?? '-'}</p>
											<p class="text-xs text-slate-500">{p.user?.email ?? '-'}</p>
										</div>
									</div>
								</td>
								<td class="px-4 py-3 font-mono text-xs text-slate-700">{p.nik ?? '—'}</td>
								<td class="px-4 py-3 text-xs text-slate-600">{p.phone ?? '-'}<br /><span class="text-slate-400">{p.address ? String(p.address).slice(0, 24) : '-'}</span></td>
								<td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 {lengkap ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-amber-50 text-amber-700 ring-amber-200'}">{lengkap ? 'Lengkap' : 'Belum lengkap'}</span></td>
								<td class="px-4 py-3 text-right">
									<div class="flex justify-end gap-2">
										<a href="/app/petugas/pasien" class="rounded-lg border px-3 py-1.5 text-xs hover:bg-slate-50">Detail</a>
										<button onclick={() => pilihPasien(p)} class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-700">Pilih & Daftar</button>
									</div>
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
		<p class="mt-3 text-xs text-slate-500">{patients.length} pasien — data dipakai ulang di kunjungan berikutnya.</p>
	{/if}
</div>
