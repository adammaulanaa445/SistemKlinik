<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let visits = $state([]);
	let loading = $state(true);

	const statusLabel = {
		menunggu: 'Menunggu',
		diperiksa: 'Diperiksa',
		selesai: 'Selesai',
		batal: 'Batal'
	};

	onMount(async () => {
		if (!requireRole('pasien')) return;
		try {
			const res = await api.get('/visits/my');
			visits = res.data;
		} finally {
			loading = false;
		}
	});
</script>

<div class="mx-auto max-w-4xl">
	<h1 class="text-xl font-bold tracking-tight text-slate-900">Riwayat Kunjungan</h1>
	<p class="mb-5 text-sm text-slate-500">Daftar kunjungan selesai dan riwayat berobat Anda.</p>

	{#if loading}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if visits.length === 0}
		<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
			<p class="text-sm font-medium text-slate-700">Belum ada riwayat kunjungan</p>
			<p class="mt-1 text-xs text-slate-500">Riwayat akan muncul setelah kunjungan selesai dan diperiksa dokter.</p>
		</div>
	{:else}
		<div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
			<div class="overflow-x-auto">
				<table class="w-full text-left text-sm">
					<thead class="border-b bg-slate-50 text-xs font-medium text-slate-500">
						<tr>
							<th class="px-4 py-3">Tanggal</th>
							<th class="px-4 py-3">Poli</th>
							<th class="px-4 py-3">Dokter</th>
							<th class="px-4 py-3">Status</th>
							<th class="px-4 py-3">Pembayaran</th>
						</tr>
					</thead>
					<tbody>
						{#each visits as v}
							<tr class="border-b last:border-0 hover:bg-slate-50/60">
								<td class="px-4 py-3 text-xs text-slate-600">
									{new Date(v.visit_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
								</td>
								<td class="px-4 py-3 font-medium text-slate-800">{v.polyclinic.name}</td>
								<td class="px-4 py-3 text-slate-600">{v.doctor.user.name}</td>
								<td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">{statusLabel[v.status] ?? v.status}</span></td>
								<td class="px-4 py-3 text-xs">
									{#if v.payment}
										<span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 {v.payment.status === 'lunas' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'}">
											{v.payment.status === 'lunas' ? 'Lunas' : 'Belum bayar'} · {v.payment.payment_code ?? ''}
										</span>
									{:else}
										<span class="text-slate-400">—</span>
									{/if}
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
</div>
