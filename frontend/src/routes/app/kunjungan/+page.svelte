<script>
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let polyclinics = $state([]);
	let doctors = $state([]);
	let selectedPoli = $state(null);
	let selectedDoctor = $state(null);
	let needsProfile = $state(false);
	let loading = $state(true);
	let submitting = $state(false);
	let error = $state('');
	let errors = $state({});

	let form = $state({
		complaint: '',
		nik: '',
		gender: 'L',
		birth_date: '',
		address: ''
	});

	onMount(async () => {
		if (!requireRole('pasien')) return;
		try {
			const [poli, me] = await Promise.all([api.get('/polyclinics'), api.get('/me')]);
			polyclinics = poli.data;

			// "Cek data pasien": kalau belum lengkap, form meminta data diri (sekali saja)
			const p = me.patient;
			needsProfile = !p || !p.nik || !p.gender || !p.birth_date || !p.address;
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
		errors = {};
		submitting = true;

		const payload = { doctor_id: selectedDoctor.id, complaint: form.complaint };

		if (needsProfile) {
			payload.nik = form.nik;
			payload.gender = form.gender;
			payload.birth_date = form.birth_date;
			payload.address = form.address;
		}

		try {
			const res = await api.post('/visits', payload);
			goto(`/app/antrian/${res.data.visit.id}`);
		} catch (err) {
			errors = err.errors ?? {};
			error = err.errors ? '' : err.message || 'Gagal mendaftar kunjungan';
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
			{#if needsProfile}
				<div class="rounded-lg bg-teal-50 p-3 text-sm text-teal-800">
					Lengkapi data diri Anda. Cukup diisi sekali, kunjungan berikutnya tidak perlu lagi.
				</div>

				<div>
					<label class="mb-1 block text-sm" for="nik">NIK</label>
					<input id="nik" bind:value={form.nik} maxlength="16" required class="w-full rounded-lg border px-3 py-2" />
					{#if errors.nik}<p class="mt-1 text-xs text-red-600">{errors.nik[0]}</p>{/if}
				</div>

				<div class="grid grid-cols-2 gap-3">
					<div>
						<label class="mb-1 block text-sm" for="gender">Jenis Kelamin</label>
						<select id="gender" bind:value={form.gender} class="w-full rounded-lg border px-3 py-2">
							<option value="L">Laki-laki</option>
							<option value="P">Perempuan</option>
						</select>
					</div>
					<div>
						<label class="mb-1 block text-sm" for="birth_date">Tanggal Lahir</label>
						<input id="birth_date" type="date" bind:value={form.birth_date} required class="w-full rounded-lg border px-3 py-2" />
						{#if errors.birth_date}<p class="mt-1 text-xs text-red-600">{errors.birth_date[0]}</p>{/if}
					</div>
				</div>

				<div>
					<label class="mb-1 block text-sm" for="address">Alamat</label>
					<textarea id="address" bind:value={form.address} required rows="2" class="w-full rounded-lg border px-3 py-2"></textarea>
					{#if errors.address}<p class="mt-1 text-xs text-red-600">{errors.address[0]}</p>{/if}
				</div>
			{/if}

			<div>
				<label class="mb-1 block text-sm" for="complaint">Keluhan</label>
				<textarea
					id="complaint"
					bind:value={form.complaint}
					required
					rows="3"
					class="w-full rounded-lg border px-3 py-2"
					placeholder="Jelaskan keluhan Anda..."
				></textarea>
				{#if errors.complaint}<p class="mt-1 text-xs text-red-600">{errors.complaint[0]}</p>{/if}
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