<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { auth, requireRole } from '$lib/auth.svelte.js';

	let summary = $state(null);
	let prescriptions = $state([]);
	let loading = $state(true);
	let activeTab = $state('menunggu');
	let working = $state(null);

	const tabs = [
		{ key: 'menunggu', label: 'Menunggu' },
		{ key: 'tertunda', label: 'Tertunda' },
		{ key: 'diproses', label: 'Diproses' },
		{ key: 'selesai', label: 'Selesai' }
	];

	onMount(async () => {
		if (!requireRole('farmasi', 'admin')) return;
		await loadAll();
		loading = false;
	});

	async function loadAll() {
		const [s, p] = await Promise.all([
			api.get('/dashboard/pharmacy'),
			api.get(`/prescriptions?status=${activeTab}`)
		]);
		summary = s.data;
		prescriptions = p.data;
	}

	async function gantiTab(tab) {
		activeTab = tab;
		const res = await api.get(`/prescriptions?status=${tab}`);
		prescriptions = res.data;
	}

	async function proses(id) {
		working = id;
		try {
			await api.patch(`/prescriptions/${id}/process`, {});
			await gantiTab(activeTab);
		} catch (err) {
			alert(err.message + (err.shortages ? '\n' + err.shortages.map(s => `${s.medicine}: butuh ${s.needed}, tersedia ${s.available}`).join('\n') : ''));
		} finally {
			working = null;
		}
	}

	async function tunda(id) {
		working = id;
		try {
			await api.patch(`/prescriptions/${id}/hold`, {});
			await gantiTab(activeTab);
		} finally {
			working = null;
		}
	}

	async function selesaikan(id) {
		working = id;
		try {
			await api.patch(`/prescriptions/${id}/complete`, {});
			await gantiTab(activeTab);
		} finally {
			working = null;
		}
	}
</script>

<h1 class="mb-1 text-xl font-bold text-gray-800">Selamat datang, {auth.user?.name}</h1>
<p class="mb-6 text-sm text-gray-500">Ringkasan resep hari ini.</p>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if summary}
	<div class="grid grid-cols-4 gap-4">
		<div class="rounded-xl border bg-white p-4 text-center shadow-sm">
			<p class="text-2xl font-bold text-amber-600">{summary.menunggu}</p>
			<p class="text-xs text-gray-500">Menunggu</p>
		</div>
		<div class="rounded-xl border bg-white p-4 text-center shadow-sm">
			<p class="text-2xl font-bold text-red-500">{summary.tertunda}</p>
			<p class="text-xs text-gray-500">Tertunda</p>
		</div>
		<div class="rounded-xl border bg-white p-4 text-center shadow-sm">
			<p class="text-2xl font-bold text-blue-500">{summary.diproses}</p>
			<p class="text-xs text-gray-500">Diproses</p>
		</div>
		<div class="rounded-xl border bg-white p-4 text-center shadow-sm">
			<p class="text-2xl font-bold text-teal-600">{summary.selesai}</p>
			<p class="text-xs text-gray-500">Selesai</p>
		</div>
	</div>

	{#if summary.stok_menipis.length > 0}
		<div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
			Stok menipis: {summary.stok_menipis.map((m) => `${m.name} (${m.stock} ${m.unit})`).join(', ')}
		</div>
	{/if}

	<div class="mt-6 flex gap-2 border-b">
		{#each tabs as tab}
			<button
				onclick={() => gantiTab(tab.key)}
				class="px-4 py-2 text-sm {activeTab === tab.key
					? 'border-b-2 border-teal-600 font-medium text-teal-700'
					: 'text-gray-500'}"
			>
				{tab.label}
			</button>
		{/each}
	</div>

	<div class="mt-4 space-y-3">
		{#if prescriptions.length === 0}
			<p class="text-sm text-gray-500">Tidak ada resep.</p>
		{/if}
		{#each prescriptions as resep}
			<div class="rounded-xl border bg-white p-4 shadow-sm">
				<div class="flex items-start justify-between">
					<div>
						<p class="font-medium text-gray-800">
							{resep.medical_record.visit.patient.user.name}
						</p>
						<p class="text-xs text-gray-500">
							{resep.medical_record.visit.doctor.user.name}
						</p>
					</div>
					<div class="flex gap-2">
						{#if resep.status === 'menunggu'}
							<button
								onclick={() => tunda(resep.id)}
								disabled={working === resep.id}
								class="rounded-lg border px-3 py-1.5 text-sm hover:bg-gray-50 disabled:opacity-50"
							>
								Tandai Tertunda
							</button>
							<button
								onclick={() => proses(resep.id)}
								disabled={working === resep.id}
								class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm text-white hover:bg-teal-700 disabled:opacity-50"
							>
								Proses
							</button>
						{:else if resep.status === 'tertunda'}
							<button
								onclick={() => proses(resep.id)}
								disabled={working === resep.id}
								class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm text-white hover:bg-teal-700 disabled:opacity-50"
							>
								Proses Ulang
							</button>
						{:else if resep.status === 'diproses'}
							<button
								onclick={() => selesaikan(resep.id)}
								disabled={working === resep.id}
								class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm text-white hover:bg-teal-700 disabled:opacity-50"
							>
								Serahkan Obat
							</button>
						{/if}
					</div>
				</div>
				<ul class="mt-2 text-sm text-gray-600">
					{#each resep.items as item}
						<li>{item.medicine.name} &times;{item.quantity} &mdash; {item.dosage}</li>
					{/each}
				</ul>
			</div>
		{/each}
	</div>
{/if}    