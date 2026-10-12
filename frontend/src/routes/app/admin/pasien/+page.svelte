<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let patients = $state([]);
	let loading = $state(true);
	let error = $state('');
	let search = $state('');
	let debounce;

	onMount(async () => {
		if (!requireRole('admin')) return;
		await load();
		loading = false;
	});

	async function load(q = '') {
		error = '';
		try {
			const path = q ? `/patients?search=${encodeURIComponent(q)}` : '/patients';
			const res = await api.get(path);
			patients = res.data;
		} catch (e) {
			error = e.message || 'Gagal memuat data pasien';
		}
	}

	function onSearchInput() {
		clearTimeout(debounce);
		debounce = setTimeout(() => load(search.trim()), 350);
	}

	async function openDetail(p) {
		try {
			const res = await api.get(`/patients/${p.id}`);
			alert(`Pasien: ${res.data.user?.name}\nNIK: ${res.data.nik}\nKunjungan: ${res.data.visits_count ?? '-'}\nEmail: ${res.data.user?.email ?? '-'}`);
		} catch (e) {
			alert(e.message || 'Gagal memuat detail');
		}
	}
</script>

<h1 class="text-xl font-bold text-gray-800">Data Pasien</h1>
<p class="mb-4 text-sm text-gray-500">Sumber: <code>/patients</code> — cari nama, email, atau NIK.</p>

<div class="mb-4 flex gap-2">
	<input bind:value={search} oninput={onSearchInput} placeholder="Cari nama / email / NIK..." class="w-full max-w-md rounded-lg border px-3 py-2 text-sm" />
	<button onclick={() => load(search.trim())} class="rounded-lg border bg-white px-4 py-2 text-sm hover:bg-gray-50">Cari</button>
	<button onclick={() => { search = ''; load(); }} class="text-sm text-teal-700 hover:underline">Reset</button>
</div>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if error}
	<p class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</p>
{:else}
	<div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
		<table class="w-full text-left text-sm">
			<thead class="bg-gray-50 text-xs text-gray-500">
				<tr><th class="px-4 py-3">Pasien</th><th class="px-4 py-3">NIK</th><th class="px-4 py-3">Gender</th><th class="px-4 py-3">Telepon</th><th class="px-4 py-3">Alamat</th><th class="px-4 py-3">Kunjungan</th></tr>
			</thead>
			<tbody>
				{#each patients as p}
					<tr class="border-t hover:bg-gray-50">
						<td class="px-4 py-3"><p class="font-medium text-gray-800">{p.user?.name ?? '-'}</p><p class="text-xs text-gray-500">{p.user?.email ?? ''}</p></td>
						<td class="px-4 py-3 font-mono text-xs">{p.nik}</td>
						<td class="px-4 py-3">{p.gender === 'L' ? 'Laki-laki' : p.gender === 'P' ? 'Perempuan' : p.gender}</td>
						<td class="px-4 py-3">{p.phone ?? '-'}</td>
						<td class="px-4 py-3 max-w-[220px] truncate text-xs text-gray-600" title={p.address}>{p.address ?? '-'}</td>
						<td class="px-4 py-3"><button onclick={() => openDetail(p)} class="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 hover:bg-teal-100">Lihat ({p.visits_count ?? 'detail'})</button></td>
					</tr>
				{/each}
				{#if patients.length === 0}<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada pasien ditemukan.</td></tr>{/if}
			</tbody>
		</table>
	</div>
	<p class="mt-2 text-xs text-gray-500">Total: {patients.length} pasien</p>
{/if}
