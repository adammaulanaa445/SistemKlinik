<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';
	import { auth } from '$lib/auth.svelte.js';
	let data = $state(null);
	let loading = $state(true);
	let error = $state('');
	onMount(async () => {
		if (!requireRole('admin')) return;
		try { const res = await api.get('/dashboard/admin'); data = res.data; } catch (e) { error = e.message || 'Gagal memuat dashboard'; } finally { loading = false; }
	});
</script>
<h1 class="mb-1 text-xl font-bold text-gray-800">Dashboard Admin</h1>
<p class="mb-6 text-sm text-gray-500">Halo {auth.user?.name} — ringkasan klinik hari ini.</p>
{#if loading}<p class="text-sm text-gray-500">Memuat...</p>
{:else if error}<p class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</p>
{:else if data}<div class="grid grid-cols-2 gap-4 md:grid-cols-4">
<div class="rounded-xl border bg-white p-4 text-center shadow-sm"><p class="text-2xl font-bold">{data.total_pasien}</p><p class="text-xs text-gray-500">Total Pasien</p></div>
<div class="rounded-xl border bg-white p-4 text-center shadow-sm"><p class="text-2xl font-bold">{data.total_dokter}</p><p class="text-xs text-gray-500">Total Dokter</p></div>
<div class="rounded-xl border bg-white p-4 text-center shadow-sm"><p class="text-2xl font-bold">{data.kunjungan_hari_ini}</p><p class="text-xs text-gray-500">Kunjungan Hari Ini</p></div>
<div class="rounded-xl border bg-white p-4 text-center shadow-sm"><p class="text-2xl font-bold text-amber-600">{data.antrian_menunggu}</p><p class="text-xs text-gray-500">Antrian Menunggu</p></div>
<div class="rounded-xl border bg-white p-4 text-center shadow-sm"><p class="text-2xl font-bold text-teal-600">Rp {Number(data.pendapatan_hari_ini).toLocaleString('id-ID')}</p><p class="text-xs text-gray-500">Pendapatan Hari Ini</p></div>
<div class="rounded-xl border bg-white p-4 text-center shadow-sm"><p class="text-2xl font-bold text-red-600">{data.pembayaran_tertunda}</p><p class="text-xs text-gray-500">Pembayaran Tertunda</p></div>
</div>
{#if data.kunjungan_per_poli_hari_ini?.length}<h2 class="mt-8 mb-3 font-semibold text-gray-700">Kunjungan per Poli Hari Ini</h2><div class="space-y-2">{#each data.kunjungan_per_poli_hari_ini as row}<div class="flex justify-between rounded-lg border bg-white px-4 py-2 text-sm"><span>{row.poli}</span><span class="font-medium">{row.total}</span></div>{/each}</div>{/if}
{#if data.kunjungan_7_hari?.length}<h2 class="mt-8 mb-3 font-semibold text-gray-700">Kunjungan 7 Hari Terakhir</h2><div class="space-y-2">{#each data.kunjungan_7_hari as row}<div class="flex justify-between rounded-lg border bg-white px-4 py-2 text-sm"><span>{row.visit_date}</span><span class="font-medium">{row.total}</span></div>{/each}</div>{/if}
{/if}
