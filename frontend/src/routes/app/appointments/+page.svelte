<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let appointments = $state([]);
	let loading = $state(true);
	let error = $state('');
	let actionId = $state(null);

	onMount(async () => {
		if (!requireRole('petugas', 'dokter', 'admin')) return;
		await load();
	});

	async function load() {
		loading = true;
		error = '';
		try {
			const res = await api.get('/appointments/today');
			appointments = res.data ?? [];
		} catch (e) {
			error = e.message || 'Gagal memuat appointment';
		} finally {
			loading = false;
		}
	}

	async function checkIn(id) {
		actionId = id;
		try {
			await api.patch(`/appointments/${id}/check-in`);
			await load();
		} catch (e) {
			error = e.message || 'Gagal check-in';
		} finally {
			actionId = null;
		}
	}

	async function cancel(id) {
		if (!confirm('Batalkan appointment ini?')) return;
		actionId = id;
		try {
			await api.patch(`/appointments/${id}/cancel`, { reason: 'Dibatalkan petugas' });
			await load();
		} catch (e) {
			error = e.message || 'Gagal membatalkan';
		} finally {
			actionId = null;
		}
	}
</script>

<div class="mx-auto max-w-5xl">
	<h1 class="text-xl font-bold tracking-tight text-slate-900">Appointment Hari Ini</h1>
	<p class="mb-5 text-sm text-slate-500">Daftar booking pasien dan estimasi jam layanan berdasarkan antrian.</p>

	{#if loading}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if error}
		<p class="rounded-2xl border bg-red-50 p-6 text-center text-sm text-red-600 shadow-sm">{error}</p>
	{:else if appointments.length === 0}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Belum ada appointment hari ini.</p>
	{:else}
		<div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="bg-slate-50">
						<tr>
							<th class="px-4 py-3 text-left">Antrian</th>
							<th class="px-4 py-3 text-left">Pasien</th>
							<th class="px-4 py-3 text-left">Dokter</th>
							<th class="px-4 py-3 text-left">Estimasi</th>
							<th class="px-4 py-3 text-left">Status</th>
							<th class="px-4 py-3 text-left">Aksi</th>
						</tr>
					</thead>
					<tbody>
						{#each appointments as a}
							<tr class="border-t">
								<td class="px-4 py-3 font-mono text-xs">{a.queue?.queue_number}</td>
								<td class="px-4 py-3">{a.patient?.user?.name ?? '-'}</td>
								<td class="px-4 py-3">{a.doctor?.user?.name ?? '-'}</td>
								<td class="px-4 py-3 text-xs">{a.estimated_start_time ?? '-'}</td>
								<td class="px-4 py-3 text-xs">{a.appointment_status}</td>
								<td class="px-4 py-3">
									<div class="flex gap-2">
										{#if a.appointment_status === 'booked'}
											<button onclick={() => checkIn(a.id)} disabled={actionId === a.id} class="rounded-lg bg-teal-600 px-3 py-1 text-xs font-semibold text-white hover:bg-teal-700 disabled:opacity-50">Check-in</button>
										{/if}
										{#if !['completed','cancelled'].includes(a.appointment_status)}
											<button onclick={() => cancel(a.id)} disabled={actionId === a.id} class="rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50">Batal</button>
										{/if}
									</div>
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
</div>