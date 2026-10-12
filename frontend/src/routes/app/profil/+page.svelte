<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { auth } from '$lib/auth.svelte.js';
	import { goto } from '$app/navigation';

	let profile = $state(null);
	let loading = $state(true);
	let error = $state('');
	let editing = $state(false);
	let saving = $state(false);
	let success = $state('');
	let form = $state({ phone: '', address: '' });

	const roleLabel = {
		admin: 'Administrator',
		petugas: 'Petugas',
		dokter: 'Dokter',
		farmasi: 'Farmasi',
		pasien: 'Pasien'
	};
	const genderLabel = { L: 'Laki-laki', P: 'Perempuan' };

	onMount(async () => {
		if (!auth.token) { goto('/login'); return; }
		try {
			const res = await api.get('/me');
			const data = res.data ?? res;
			profile = data;
			if (data.patient) {
				form.phone = data.patient.phone ?? '';
				form.address = data.patient.address ?? '';
			}
		} catch (err) {
			error = err.message || 'Gagal memuat profil';
		} finally {
			loading = false;
		}
	});

	async function save() {
		if (!profile?.patient) return;
		error = ''; success = ''; saving = true;
		try {
			const res = await api.patch('/patients/' + profile.patient.id, {
				phone: form.phone,
				address: form.address
			});
			const updated = res.data ?? res;
			profile.patient = { ...profile.patient, ...updated };
			const fresh = await api.get('/me');
			profile = fresh.data ?? fresh;
			if (profile.patient) {
				form.phone = profile.patient.phone ?? '';
				form.address = profile.patient.address ?? '';
			}
			success = 'Profil berhasil diperbarui';
			editing = false;
		} catch (err) {
			error = err.message || 'Gagal menyimpan';
			if (err.errors) {
				const msgs = Object.values(err.errors).flat().join(', ');
				if (msgs) error = msgs;
			}
		} finally { saving = false; }
	}

	function cancel() {
		if (profile?.patient) {
			form.phone = profile.patient.phone ?? '';
			form.address = profile.patient.address ?? '';
		}
		editing = false; error = ''; success = '';
	}

	function formatDate(d) {
		if (!d) return '-';
		try { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); } catch { return d; }
	}
</script>

<div class="mx-auto max-w-3xl">
	<h1 class="text-xl font-bold text-slate-800">Profil Saya</h1>
	<p class="mb-2 text-sm text-slate-500">Informasi akun dan data pasien Anda.</p>
	<div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2.5 text-xs leading-relaxed text-teal-800">💡 <span class="font-semibold">Input sekali, pakai berkali-kali:</span> NIK, jenis kelamin, tanggal lahir, dan alamat yang Anda isi di kunjungan pertama akan tersimpan dan otomatis dipakai di kunjungan berikutnya. Tidak perlu isi ulang — cukup pilih poli, dokter, dan keluhan.</div>

	{#if loading}
		<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
			<p class="text-sm text-slate-500">Memuat profil...</p>
		</div>
	{:else if error && !profile}
		<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>
	{:else if profile}
		<div class="rounded-2xl border bg-white p-6 shadow-sm">
			<div class="flex items-center gap-4">
				<div class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-600 text-lg font-bold text-white">
					{profile.name?.charAt(0)?.toUpperCase() ?? '?'}
				</div>
				<div>
					<p class="text-base font-semibold text-slate-800">{profile.name}</p>
					<p class="text-sm text-slate-500">{profile.email}</p>
					<span class="mt-1 inline-flex rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 ring-1 ring-teal-200">{roleLabel[profile.role] ?? profile.role}</span>
				</div>
			</div>
			<div class="mt-6 grid gap-4 sm:grid-cols-2">
				<div class="rounded-xl bg-slate-50 p-4">
					<p class="text-xs font-medium text-slate-500">Nama</p>
					<p class="mt-1 text-sm font-semibold text-slate-800">{profile.name}</p>
				</div>
				<div class="rounded-xl bg-slate-50 p-4">
					<p class="text-xs font-medium text-slate-500">Email</p>
					<p class="mt-1 text-sm font-semibold text-slate-800">{profile.email}</p>
				</div>
				<div class="rounded-xl bg-slate-50 p-4">
					<p class="text-xs font-medium text-slate-500">Role</p>
					<p class="mt-1 text-sm font-semibold text-slate-800">{roleLabel[profile.role] ?? profile.role}</p>
				</div>
				<div class="rounded-xl bg-slate-50 p-4">
					<p class="text-xs font-medium text-slate-500">ID Akun</p>
					<p class="mt-1 text-sm font-semibold text-slate-800">#{profile.id}</p>
				</div>
			</div>
		</div>

		{#if profile.patient}
			<div class="mt-6 rounded-2xl border bg-white p-6 shadow-sm">
				<div class="mb-4 flex items-center justify-between">
					<h2 class="text-sm font-semibold text-slate-800">Data Pasien</h2>
					{#if !editing}
						<button onclick={() => (editing = true)} class="rounded-full bg-teal-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-teal-700">Edit</button>
					{/if}
				</div>
				{#if success}<p class="mb-3 rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-700">{success}</p>{/if}
				{#if error}<p class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>{/if}
				{#if !editing}
					<div class="grid gap-4 sm:grid-cols-2">
						<div class="rounded-xl bg-slate-50 p-4">
							<p class="text-xs font-medium text-slate-500">NIK</p>
							<p class="mt-1 text-sm font-semibold text-slate-800">{profile.patient.nik ?? '-'}</p>
						</div>
						<div class="rounded-xl bg-slate-50 p-4">
							<p class="text-xs font-medium text-slate-500">Jenis Kelamin</p>
							<p class="mt-1 text-sm font-semibold text-slate-800">{genderLabel[profile.patient.gender] ?? profile.patient.gender ?? '-'}</p>
						</div>
						<div class="rounded-xl bg-slate-50 p-4">
							<p class="text-xs font-medium text-slate-500">Tanggal Lahir</p>
							<p class="mt-1 text-sm font-semibold text-slate-800">{formatDate(profile.patient.birth_date)}</p>
						</div>
						<div class="rounded-xl bg-slate-50 p-4">
							<p class="text-xs font-medium text-slate-500">Telepon</p>
							<p class="mt-1 text-sm font-semibold text-slate-800">{profile.patient.phone ?? '-'}</p>
						</div>
						<div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
							<p class="text-xs font-medium text-slate-500">Alamat</p>
							<p class="mt-1 text-sm font-semibold text-slate-800">{profile.patient.address ?? '-'}</p>
						</div>
					</div>
					<p class="mt-4 text-xs text-slate-400">NIK, jenis kelamin, dan tanggal lahir hanya diisi saat pendaftaran kunjungan pertama. Telepon dan alamat dapat diedit di sini.</p>
				{:else}
					<form onsubmit={(e) => { e.preventDefault(); save(); }} class="space-y-4">
						<div class="grid gap-4 sm:grid-cols-2">
							<div class="rounded-xl bg-slate-50 p-4">
								<p class="text-xs font-medium text-slate-500">NIK</p>
								<p class="mt-1 text-sm text-slate-600">{profile.patient.nik ?? '-'}</p>
							</div>
							<div class="rounded-xl bg-slate-50 p-4">
								<p class="text-xs font-medium text-slate-500">Jenis Kelamin / Lahir</p>
								<p class="mt-1 text-sm text-slate-600">{(genderLabel[profile.patient.gender] ?? profile.patient.gender ?? '-') + ' \u00b7 ' + formatDate(profile.patient.birth_date)}</p>
							</div>
						</div>
						<div>
							<label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Telepon</label>
							<input id="phone" bind:value={form.phone} required maxlength="20" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100" placeholder="08xxxxxxxxxx" />
						</div>
						<div>
							<label for="address" class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
							<textarea id="address" bind:value={form.address} required rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100" placeholder="Alamat lengkap"></textarea>
						</div>
						<div class="flex gap-2">
							<button type="submit" disabled={saving} class="flex-1 rounded-xl bg-teal-600 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50">{saving ? 'Menyimpan...' : 'Simpan'}</button>
							<button type="button" onclick={cancel} class="rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
						</div>
					</form>
				{/if}
			</div>
		{:else}
			<div class="mt-6 rounded-2xl border bg-white p-6 shadow-sm">
				<p class="text-sm text-slate-500">Akun ini bukan pasien — tidak ada data pasien tambahan.</p>
			</div>
		{/if}
	{/if}
</div>
