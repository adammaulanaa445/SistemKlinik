<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { auth, requireRole } from '$lib/auth.svelte.js';

	let queues = $state([]);
	let payments = $state([]);
	let loading = $state(true);
	let activeTab = $state('antrian');
	let working = $state(null);
	let error = $state('');

	const statusLabel = {
		menunggu: 'Menunggu',
		dipanggil: 'Dipanggil',
		diproses: 'Diperiksa',
		selesai: 'Selesai'
	};

	onMount(async () => {
		if (!requireRole('petugas', 'admin')) return;
		await loadAll();
		loading = false;
	});

	async function loadAll() {
		const [q, p] = await Promise.all([
			api.get('/queues/today'),
			api.get('/payments?status=belum_bayar')
		]);
		queues = q.data;
		payments = p.data;
	}

	async function panggil(queueId) {
		error = '';
		working = 'q' + queueId;
		try {
			await api.patch(`/queues/${queueId}/call`, {});
			await loadAll();
		} catch (err) {
			error = err.message || 'Gagal memanggil pasien';
		} finally {
			working = null;
		}
	}

	async function bayar(paymentId, method) {
		error = '';
		working = 'p' + paymentId;
		try {
			await api.patch(`/payments/${paymentId}/pay`, { method });
			await loadAll();
		} catch (err) {
			error = err.message || 'Gagal mencatat pembayaran';
		} finally {
			working = null;
		}
	}
</script>

<h1 class="mb-1 text-xl font-bold text-gray-800">Selamat datang, {auth.user?.name}</h1>
<p class="mb-6 text-sm text-gray-500">Pantau antrian, panggil pasien, dan kelola pembayaran.</p>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else}
	<div class="flex items-center justify-between border-b">
		<div class="flex gap-2">
			<button
				onclick={() => (activeTab = 'antrian')}
				class="px-4 py-2 text-sm {activeTab === 'antrian' ? 'border-b-2 border-teal-600 font-medium text-teal-700' : 'text-gray-500'}"
			>
				Antrian Hari Ini
			</button>
			<button
				onclick={() => (activeTab = 'pembayaran')}
				class="px-4 py-2 text-sm {activeTab === 'pembayaran' ? 'border-b-2 border-teal-600 font-medium text-teal-700' : 'text-gray-500'}"
			>
				Pembayaran Belum Lunas
			</button>
		</div>
		<button onclick={loadAll} class="text-sm text-teal-700 hover:underline">Muat ulang</button>
	</div>

	{#if error}<p class="mt-3 text-sm text-red-600">{error}</p>{/if}

	{#if activeTab === 'antrian'}
		<div class="mt-4 space-y-3">
			{#if queues.length === 0}
				<p class="text-sm text-gray-500">Belum ada antrian hari ini.</p>
			{/if}
			{#each queues as q}
				<div class="flex items-center justify-between rounded-xl border bg-white p-4 shadow-sm">
					<div>
						<p class="font-medium text-gray-800">{q.queue_number} &middot; {q.visit.patient.user.name}</p>
						<p class="text-xs text-gray-500">
							{q.visit.polyclinic.name} &middot; {q.visit.doctor.user.name}
						</p>
					</div>
					<div class="flex items-center gap-3">
						<span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
							{statusLabel[q.status]}
						</span>
						{#if q.status === 'menunggu'}
							<button
								onclick={() => panggil(q.id)}
								disabled={working === 'q' + q.id}
								class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm text-white hover:bg-teal-700 disabled:opacity-50"
							>
								Panggil
							</button>
						{/if}
					</div>
				</div>
			{/each}
		</div>
	{:else}
		<div class="mt-4 space-y-3">
			{#if payments.length === 0}
				<p class="text-sm text-gray-500">Tidak ada tagihan yang belum lunas.</p>
			{/if}
			{#each payments as pay}
				<div class="rounded-xl border bg-white p-4 shadow-sm">
					<div class="flex items-center justify-between">
						<div>
							<p class="font-medium text-gray-800">{pay.payment_code}</p>
							<p class="text-xs text-gray-500">{pay.visit.patient.user.name} &middot; {pay.visit.polyclinic.name}</p>
						</div>
						<p class="font-semibold text-gray-800">
							Rp {Number(pay.amount).toLocaleString('id-ID')}
						</p>
					</div>
					<div class="mt-3 flex gap-2">
						{#each ['tunai', 'transfer', 'qris'] as method}
							<button
								onclick={() => bayar(pay.id, method)}
								disabled={working === 'p' + pay.id}
								class="rounded-lg border px-3 py-1.5 text-sm capitalize hover:bg-gray-50 disabled:opacity-50"
							>
								Bayar {method}
							</button>
						{/each}
					</div>
				</div>
			{/each}
		</div>
	{/if}
{/if}