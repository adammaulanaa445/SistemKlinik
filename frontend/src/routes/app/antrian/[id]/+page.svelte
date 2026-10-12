<script>
	import { page } from '$app/state';
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';

	let visit = $state(null);
	let loading = $state(true);
	let serving = $state(null);

	const statusLabel = {
		menunggu: 'Menunggu', dipanggil: 'Dipanggil', diproses: 'Diperiksa', selesai: 'Selesai'
	};

	const steps = ['menunggu', 'dipanggil', 'selesai'];
	function stepIndex(status) {
		if (status === 'diproses') return 1;
		const i = steps.indexOf(status);
		return i === -1 ? 0 : i;
	}

	onMount(async () => {
		try {
			const res = await api.get(`/visits/${page.params.id}/queue`);
			visit = res.data;
			// coba ambil antrian yang sedang dilayani di poli yang sama hari ini (opsional)
			try {
				const q = await api.get('/queues/today?polyclinic_id=' + visit.polyclinic_id);
				const list = q.data ?? [];
				// cari yang status dipanggil/diproses terkecil
				const active = list.filter((x) => ['dipanggil', 'diproses'].includes(x.status)).sort((a,b)=> String(a.queue_number).localeCompare(String(b.queue_number)));
				if (active[0]) serving = active[0].queue_number;
			} catch {}
		} finally {
			loading = false;
		}
	});
</script>

<div class="mx-auto max-w-xl">
	<h1 class="text-xl font-bold tracking-tight text-slate-900">Antrian Saya</h1>
	<p class="mb-5 text-sm text-slate-500">Pantau status kunjungan Anda.</p>

	{#if loading}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if visit}
		{@const st = visit.queue?.status ?? visit.status}
		{@const idx = stepIndex(st)}
		<div class="rounded-2xl border bg-white p-6 shadow-sm">
			<p class="text-center text-xs font-medium tracking-wide text-slate-500">{visit.polyclinic.name}</p>
			<div class="mt-2 grid grid-cols-2 gap-3 text-center">
				<div class="rounded-2xl bg-teal-50 p-4 ring-1 ring-teal-100">
					<p class="text-xs font-medium text-teal-700">Nomor Antrian</p>
					<p class="mt-1 text-4xl font-extrabold tracking-tight text-teal-600">{visit.queue?.queue_number ?? '-'}</p>
				</div>
				<div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
					<p class="text-xs font-medium text-slate-500">Sedang Dilayani</p>
					<p class="mt-1 text-2xl font-bold tracking-tight text-slate-700">{serving ?? '—'}</p>
					<p class="text-xs text-slate-400">{serving ? 'Estimasi sesuai urutan' : 'Menunggu petugas'}</p>
				</div>
			</div>
			<div class="mt-3 flex justify-center">
				<span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {st==='menunggu' ? 'bg-amber-50 text-amber-700 ring-amber-200' : st==='dipanggil' ? 'bg-blue-50 text-blue-700 ring-blue-200' : st==='diproses' ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'}">
					{statusLabel[st] ?? st}
				</span>
			</div>

			<!-- Stepper Menunggu → Dipanggil → Selesai -->
			<div class="mt-6 flex items-center justify-between">
				{#each steps as s, i}
					<div class="flex flex-col items-center gap-1.5">
						<div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ring-1 {i < idx ? 'bg-teal-600 text-white ring-teal-600' : i === idx ? 'bg-teal-600 text-white ring-teal-600 shadow' : 'bg-white text-slate-400 ring-slate-200'}">
							{#if i < idx}✓{:else}{i + 1}{/if}
						</div>
						<span class="text-xs font-medium {i <= idx ? 'text-slate-800' : 'text-slate-400'}">{statusLabel[s]}</span>
					</div>
					{#if i < steps.length - 1}
						<div class="mx-1 h-0.5 flex-1 {i < idx ? 'bg-teal-600' : 'bg-slate-200'}"></div>
					{/if}
				{/each}
			</div>

			<div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm">
				<div class="grid gap-1.5 text-sm text-slate-600">
					<p><span class="text-slate-500">Dokter:</span> <span class="font-medium text-slate-800">{visit.doctor.user.name}</span> <span class="text-slate-400">· {visit.doctor.specialization}</span></p>
					<p><span class="text-slate-500">Tanggal:</span> {new Date(visit.visit_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })} <span class="text-slate-400">· {visit.queue?.created_at ? new Date(visit.queue.created_at).toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit'}) : ''}</span></p>
					<p><span class="text-slate-500">Keluhan:</span> {visit.complaint}</p>
					{#if visit.payment}<p><span class="text-slate-500">Pembayaran:</span> <span class="font-medium {visit.payment.status==='lunas' ? 'text-emerald-700' : 'text-amber-700'}">{visit.payment.status === 'lunas' ? 'Lunas' : 'Belum bayar'} — {visit.payment.payment_code ?? ''}</span></p>{/if}
				</div>
				<p class="mt-3 text-xs text-slate-400">Tanggal pendaftaran {new Date(visit.created_at).toLocaleString('id-ID', { day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit'})}</p>
			</div>
		</div>
		<a href="/app/pasien" class="mt-4 block text-center text-sm font-medium text-teal-700 hover:underline">← Kembali ke Dashboard</a>
	{/if}
</div>
