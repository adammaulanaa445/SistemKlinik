<script>
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';

	let polyclinics = $state([]);
	let doctors = $state([]);
	let selectedPoli = $state(null);
	let selectedDoctor = $state(null);
	let complaint = $state('');
	let loading = $state(true);
	let submitting = $state(false);
	let error = $state('');

	onMount(async () => {
		try {
			const res = await api.get('/polyclinics');
			polyclinics = res.data;
		} finally {
			loading = false;
		}
	});

	async function pilihPoli(poli) {
		selectedPoli = poli;
		selectedDoctor = null;
		const res = await api.get(`/doctors?polyclinic_id=${poli.id}`);
		doctors = res.data;
	}

	async function daftar(e) {
		e.preventDefault();
		error = '';
		submitting = true;

		try {
			const res = await api.post('/visits', {
				doctor_id: selectedDoctor.id,
				complaint
			});
			goto(`/app/antrian/${res.data.visit.id}`);
		} catch (err) {
			error = err.message || 'Gagal mendaftar kunjungan';
		} finally {
			submitting = false;
		}
	}
</script>

<h1 class="mb-6 text-xl font-bold text-gray-800">Pendaftaran Kunjungan</h1>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else if !selectedPoli}
	<p class="mb-3 text-sm font-medium text-gray-600">Pilih Poli</p>
	<div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
		{#each polyclinics as poli}
			<button
				onclick={() => pilihPoli(poli)}
				class="rounded-xl border bg-white p-4 text-left shadow-sm hover:border-teal-400"
			>
				<p class="font-medium text-gray-800">{poli.name}</p>
				<p class="text-xs text-gray-400">{poli.doctors_count} dokter</p>
			</button>
		{/each}
	</div>
{:else if !selectedDoctor}
	<button onclick={() => (selectedPoli = null)} class="mb-3 text-sm text-teal-700 hover:underline">
		&larr; Pilih poli lain
	</button>
	<p class="mb-3 text-sm font-medium text-gray-600">Pilih Dokter &mdash; {selectedPoli.name}</p>
	<div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
		{#each doctors as doctor}
			<button
				onclick={() => (selectedDoctor = doctor)}
				class="rounded-xl border bg-white p-4 text-left shadow-sm hover:border-teal-400"
			>
				<p class="font-medium text-gray-800">{doctor.user.name}</p>
				<p class="text-xs text-gray-400">{doctor.specialization}</p>
			</button>
		{/each}
	</div>
{:else}
	<button onclick={() => (selectedDoctor = null)} class="mb-3 text-sm text-teal-700 hover:underline">
		&larr; Pilih dokter lain
	</button>

	<div class="max-w-lg rounded-xl border bg-white p-5 shadow-sm">
		<p class="text-sm text-gray-500">Poli: <span class="font-medium text-gray-800">{selectedPoli.name}</span></p>
		<p class="text-sm text-gray-500">Dokter: <span class="font-medium text-gray-800">{selectedDoctor.user.name}</span></p>

		<form onsubmit={daftar} class="mt-4 space-y-3">
			<div>
				<label class="mb-1 block text-sm" for="complaint">Keluhan</label>
				<textarea
					id="complaint"
					bind:value={complaint}
					required
					rows="3"
					class="w-full rounded-lg border px-3 py-2"
					placeholder="Jelaskan keluhan Anda..."
				></textarea>
			</div>

			{#if error}<p class="text-sm text-red-600">{error}</p>{/if}

			<button
				type="submit"
				disabled={submitting}
				class="w-full rounded-lg bg-teal-600 py-2.5 text-white hover:bg-teal-700 disabled:opacity-50"
			>
				{submitting ? 'Mendaftarkan...' : 'Daftar'}
			</button>
		</form>
	</div>
{/if}