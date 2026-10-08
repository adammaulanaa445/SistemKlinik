<script>
	import { page } from '$app/state';
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	const visitId = page.params.visitId;

	let queue = $state(null);
	let history = $state([]);
	let medicines = $state([]);
	let loading = $state(true);
	let error = $state('');
	let working = $state(false);

	let form = $state({
		examination_result: '',
		diagnosis: '',
		treatment: '',
		notes: ''
	});
	let resepItems = $state([]);

	onMount(async () => {
		if (!requireRole('dokter')) return;
		try {
			await loadQueue();
			if (queue) {
				const [h, m] = await Promise.all([
					api.get(`/patients/${queue.visit.patient_id}/medical-records`),
					api.get('/medicines')
				]);
				history = h.data;
				medicines = m.data;
			}
		} finally {
			loading = false;
		}
	});

	async function loadQueue() {
		const res = await api.get('/queues/today');
		queue = res.data.find((q) => String(q.visit_id) === String(visitId)) ?? null;
	}

	async function mulaiPeriksa() {
		error = '';
		working = true;
		try {
			await api.patch(`/queues/${queue.id}/start`, {});
			await loadQueue();
		} catch (err) {
			error = err.message || 'Gagal memulai pemeriksaan';
		} finally {
			working = false;
		}
	}

	function tambahObat() {
		resepItems = [...resepItems, { medicine_id: medicines[0]?.id, quantity: 1, dosage: '' }];
	}

	function hapusObat(i) {
		resepItems = resepItems.filter((_, idx) => idx !== i);
	}

	async function simpan(e) {
		e.preventDefault();
		error = '';
		working = true;

		try {
			await api.post('/medical-records', {
				visit_id: visitId,
				...form,
				prescription_items: resepItems.length ? resepItems : undefined
			});
			goto('/app/dokter');
		} catch (err) {
			error = err.message || 'Gagal menyimpan';
		} finally {
			working = false;
		}
	}
</script>

<h1 class="mb-6 text-xl font-bold text-gray-800">Pemeriksaan Pasien</h1>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if !queue}
	<p class="text-sm text-gray-500">Antrian tidak ditemukan atau bukan milik Anda.</p>
{:else}
	<div class="max-w-2xl space-y-4">
		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<p class="font-medium text-gray-800">{queue.queue_number} &middot; {queue.visit.patient.user.name}</p>
			<p class="mt-1 text-sm text-gray-500">Keluhan: {queue.visit.complaint}</p>
		</div>

		<div class="rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="mb-2 text-sm font-semibold text-gray-700">Riwayat Medis Pasien</h2>
			{#if history.length === 0}
				<p class="text-sm text-gray-500">Belum ada riwayat medis.</p>
			{:else}
				<div class="space-y-3">
					{#each history as r}
						<div class="rounded-lg bg-gray-50 p-3 text-sm">
							<p class="text-xs text-gray-400">
								{new Date(r.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
								&middot; {r.visit.polyclinic.name}
							</p>
							<p class="font-medium text-gray-800">{r.diagnosis}</p>
							<p class="text-gray-600">{r.examination_result}</p>
							{#if r.prescription}
								<p class="mt-1 text-xs text-gray-500">
									Resep: {r.prescription.items.map((i) => i.medicine.name).join(', ')}
								</p>
							{/if}
						</div>
					{/each}
				</div>
			{/if}
		</div>

		{#if queue.status === 'menunggu'}
			<div class="rounded-xl border bg-white p-5 text-sm text-gray-600 shadow-sm">
				Menunggu pasien dipanggil oleh petugas.
				<button onclick={loadQueue} class="ml-2 text-teal-700 hover:underline">Muat ulang</button>
			</div>
		{:else if queue.status === 'dipanggil'}
			<div class="rounded-xl border bg-white p-5 shadow-sm">
				<p class="mb-3 text-sm text-gray-600">Pasien sudah dipanggil petugas ke ruang periksa.</p>
				<button
					onclick={mulaiPeriksa}
					disabled={working}
					class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700 disabled:opacity-50"
				>
					{working ? 'Memproses...' : 'Mulai Periksa'}
				</button>
				{#if error}<p class="mt-2 text-sm text-red-600">{error}</p>{/if}
			</div>
		{:else if queue.status === 'diproses'}
			<form onsubmit={simpan} class="space-y-3 rounded-xl border bg-white p-5 shadow-sm">
				<div>
					<label class="mb-1 block text-sm" for="examination_result">Hasil Pemeriksaan</label>
					<textarea id="examination_result" bind:value={form.examination_result} required rows="2" class="w-full rounded-lg border px-3 py-2"></textarea>
				</div>
				<div>
					<label class="mb-1 block text-sm" for="diagnosis">Diagnosis</label>
					<textarea id="diagnosis" bind:value={form.diagnosis} required rows="2" class="w-full rounded-lg border px-3 py-2"></textarea>
				</div>
				<div>
					<label class="mb-1 block text-sm" for="treatment">Tindakan</label>
					<textarea id="treatment" bind:value={form.treatment} rows="2" class="w-full rounded-lg border px-3 py-2"></textarea>
				</div>
				<div>
					<label class="mb-1 block text-sm" for="notes">Catatan</label>
					<textarea id="notes" bind:value={form.notes} rows="2" class="w-full rounded-lg border px-3 py-2"></textarea>
				</div>

				<div>
					<div class="mb-2 flex items-center justify-between">
						<p class="text-sm font-medium">Resep Obat</p>
						<button type="button" onclick={tambahObat} class="text-sm text-teal-700 hover:underline">
							+ Tambah Obat
						</button>
					</div>
					{#each resepItems as item, i}
						<div class="mb-2 flex gap-2">
							<select bind:value={item.medicine_id} class="flex-1 rounded-lg border px-2 py-1.5 text-sm">
								{#each medicines as med}
									<option value={med.id}>{med.name}</option>
								{/each}
							</select>
							<input type="number" bind:value={item.quantity} min="1" class="w-16 rounded-lg border px-2 py-1.5 text-sm" />
							<input bind:value={item.dosage} placeholder="Dosis" class="flex-1 rounded-lg border px-2 py-1.5 text-sm" />
							<button type="button" onclick={() => hapusObat(i)} class="text-red-500">&times;</button>
						</div>
					{/each}
				</div>

				{#if error}<p class="text-sm text-red-600">{error}</p>{/if}

				<button
					type="submit"
					disabled={working}
					class="w-full rounded-lg bg-teal-600 py-2.5 text-white hover:bg-teal-700 disabled:opacity-50"
				>
					{working ? 'Menyimpan...' : 'Simpan Rekam Medis'}
				</button>
			</form>
		{:else}
			<div class="rounded-xl border bg-white p-5 text-sm text-gray-600 shadow-sm">
				Pemeriksaan untuk antrian ini sudah selesai.
			</div>
		{/if}
	</div>
{/if}