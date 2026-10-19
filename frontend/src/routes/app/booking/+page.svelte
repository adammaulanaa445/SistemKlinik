<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let doctors = $state([]);
	let selectedDoctor = $state(null);
	let date = $state(new Date().toISOString().slice(0, 10));
	let availability = $state(null);
	let loading = $state(true);
	let booking = $state(false);
	let error = $state('');
	let complaint = $state('');
	let myAppointments = $state([]);

	onMount(async () => {
		if (!requireRole('pasien')) return;
		try {
			const res = await api.get('/doctors');
			doctors = res.data ?? [];
			await loadMyAppointments();
		} finally {
			loading = false;
		}
	});

	async function loadAvailability() {
		if (!selectedDoctor || !date) return;
		error = '';
		availability = null;
		try {
			const res = await api.get(`/appointments/availability?doctor_id=${selectedDoctor.id}&date=${date}`);
			availability = res.data;
		} catch (e) {
			error = e.message || 'Gagal memuat ketersediaan';
		}
	}

	async function loadMyAppointments() {
		try {
			const res = await api.get('/appointments/my');
			myAppointments = res.data ?? [];
		} catch {}
	}

	async function book() {
		if (!selectedDoctor || !date || !complaint.trim()) {
			error = 'Lengkapi data booking';
			return;
		}
		booking = true;
		error = '';
		try {
			await api.post('/appointments', {
				doctor_id: selectedDoctor.id,
				appointment_date: date,
				complaint: complaint.trim()
			});
			await loadMyAppointments();
			complaint = '';
			await loadAvailability();
		} catch (e) {
			error = e.message || 'Gagal booking';
		} finally {
			booking = false;
		}
	}

	$effect(() => {
		if (selectedDoctor && date) loadAvailability();
	});
</script>

<div class="mx-auto max-w-4xl">
	<h1 class="text-xl font-bold tracking-tight text-slate-900">Booking Kunjungan</h1>
	<p class="mb-5 text-sm text-slate-500">Pilih tanggal dan dokter. Jam dilayani ditentukan dari antrian.</p>

	{#if loading}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else}
		<div class="grid gap-4 md:grid-cols-2">
			<div class="rounded-2xl border bg-white p-5 shadow-sm">
				<label class="mb-2 block text-sm font-medium text-slate-700">Tanggal</label>
				<input type="date" bind:value={date} class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />

				<label class="mb-2 mt-4 block text-sm font-medium text-slate-700">Dokter</label>
				<select bind:value={selectedDoctor} class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
					<option value={null}>Pilih dokter</option>
					{#each doctors as d}
						<option value={d}>{d.user?.name} - {d.polyclinic?.name}</option>
					{/each}
				</select>

				<label class="mb-2 mt-4 block text-sm font-medium text-slate-700">Keluhan</label>
				<textarea bind:value={complaint} rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>

				{#if error}
					<p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600 ring-1 ring-red-200">{error}</p>
				{/if}

				<button
					onclick={book}
					disabled={booking || !availability || availability.remaining <= 0}
					class="mt-4 w-full rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50"
				>
					{booking ? 'Memproses...' : 'Booking'}
				</button>
			</div>

			<div class="rounded-2xl border bg-white p-5 shadow-sm">
				<h2 class="text-sm font-bold text-slate-800">Ketersediaan</h2>
				{#if availability}
					<p class="mt-2 text-sm text-slate-600">
						Kuota: {availability.booked} / {availability.schedule?.quota ?? '-'} terisi
					</p>
					<p class="text-sm text-slate-600">Sisa: {availability.remaining}</p>
					<p class="text-sm text-slate-600">Durasi per pasien: {availability.duration_per_patient} menit</p>
					<div class="mt-3 max-h-60 overflow-auto rounded-xl border">
						<table class="w-full text-xs">
							<thead class="bg-slate-50">
								<tr>
									<th class="px-2 py-1 text-left">Antrian</th>
									<th class="px-2 py-1 text-left">Estimasi</th>
									<th class="px-2 py-1 text-left">Status</th>
								</tr>
							</thead>
							<tbody>
								{#each availability.estimates as e}
									<tr class="border-t">
										<td class="px-2 py-1 font-mono">{e.queue_number}</td>
										<td class="px-2 py-1">{e.estimated_time}</td>
										<td class="px-2 py-1">{e.available ? 'Tersedia' : 'Terisi'}</td>
									</tr>
								{/each}
							</tbody>
						</table>
					</div>
				{:else}
					<p class="mt-2 text-sm text-slate-500">Pilih dokter dan tanggal.</p>
				{/if}
			</div>
		</div>

		<div class="mt-6 rounded-2xl border bg-white p-5 shadow-sm">
			<h2 class="text-sm font-bold text-slate-800">Booking Saya</h2>
			{#if myAppointments.length === 0}
				<p class="mt-2 text-sm text-slate-500">Belum ada booking.</p>
			{:else}
				<div class="mt-3 space-y-2">
					{#each myAppointments as a}
						<div class="rounded-xl border p-3 text-sm">
							<p class="font-medium text-slate-800">{a.queue?.queue_number} - {a.doctor?.user?.name}</p>
							<p class="text-xs text-slate-500">{a.appointment_date} | Estimasi {a.estimated_start_time} | Status {a.appointment_status}</p>
							<p class="text-xs text-slate-500">{a.complaint}</p>
						</div>
					{/each}
				</div>
			{/if}
		</div>
	{/if}
</div>
