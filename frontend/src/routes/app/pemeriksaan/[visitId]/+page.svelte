<script>
	import { page } from '$app/state';
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	const visitId = page.params.visitId;

	let queue = $state(null);
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
			const [q, m] = await Promise.all([
				api.get('/dashboard/doctor'),
				api.get('/medicines')
			]);
			queue = q.data.antrian_terbaru.find((x) => String(x.visit_id) === visitId) ?? null;
			medicines = m.data;
		} finally {
			loading = false;
		}
	});

	async function panggil() {
		working = true;
		try {
			await api.patch(`/queues/${queue.id}/call`, {});
			await api.patch(`/queues/${queue.id}/start`, {});
			queue = { ...queue, status: 'diproses' };
		} catch (err) {
			error = err.message;
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
	<div class="max-w-2xl rounded-xl border bg-white p-5 shadow-sm">
		<p class="text-sm text-gray-500">
			{queue.queue_number} &middot; {queue.visit.patient.user.name}
		</p>

		{#if queue.status === 'menunggu'}
			<button
				onclick={panggil}
				disabled={working}
				class="mt-3 rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700 disabled:opacity-50"
			>
				{working ? 'Memproses...' : 'Panggil & Mulai Periksa'}
			</button>
		{:else}
			<form onsubmit={simpan} class="mt-4 space-y-3">
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
		{/if}
	</div>
{/if}