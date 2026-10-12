<script>
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let polyclinics = $state([]);
	let doctors = $state([]);
	let schedules = $state([]);
	let selectedPoli = $state(null);
	let selectedDoctor = $state(null);
	let selectedSchedule = $state(null);
	let needsProfile = $state(false);
	let loading = $state(true);
	let submitting = $state(false);
	let error = $state('');
	let errors = $state({});
	let form = $state({ complaint: '', nik: '', gender: 'L', birth_date: '', address: '' });
	const hariLabel = { senin: 'Senin', selasa: 'Selasa', rabu: 'Rabu', kamis: 'Kamis', jumat: 'Jumat', sabtu: 'Sabtu', minggu: 'Minggu' };
	const poliMeta = {
		'Poli Umum': { icon: '🩺', bg: 'bg-sky-500' },
		'Poli Gigi': { icon: '🦷', bg: 'bg-emerald-500' },
		'Poli Anak': { icon: '👶', bg: 'bg-teal-500' },
		'Poli Kandungan': { icon: '🤰', bg: 'bg-pink-500' },
		'Poli Penyakit Dalam': { icon: '❤️', bg: 'bg-blue-500' },
		'Poli Mata': { icon: '👁️', bg: 'bg-cyan-500' },
		'Poli THT': { icon: '👂', bg: 'bg-indigo-400' },
		'Poli Kulit & Kelamin': { icon: '✨', bg: 'bg-amber-500' }
	};
	function metaFor(name) { return poliMeta[name] ?? { icon: '🏥', bg: 'bg-teal-500' }; }
	const step = $derived(!selectedPoli ? 1 : !selectedDoctor ? 2 : 3);
	let patientData = $state(null);
	onMount(async () => {
		if (!requireRole('pasien')) return;
		try {
			const [poli, me] = await Promise.all([api.get('/polyclinics'), api.get('/me')]);
			polyclinics = poli.data;
			const pp = me.patient;
			patientData = pp;
			needsProfile = !pp || !pp.nik || !pp.gender || !pp.birth_date || !pp.address;
		} finally { loading = false; }
	});
	async function pilihPoli(poli) {
		selectedPoli = poli;
		selectedDoctor = null;
		selectedSchedule = null;
		schedules = [];
		const res = await api.get('/doctors?polyclinic_id=' + poli.id);
		doctors = res.data;
	}
	async function pilihDokter(doctor) {
		selectedDoctor = doctor;
		selectedSchedule = null;
		try {
			const res = await api.get('/doctors/' + doctor.id);
			schedules = res.data.schedules ?? [];
		} catch { schedules = []; }
	}
	async function daftar(e) {
		e.preventDefault();
		error = ''; errors = {}; submitting = true;
		const payload = { doctor_id: selectedDoctor.id, complaint: form.complaint };
		if (selectedSchedule) payload.doctor_schedule_id = selectedSchedule.id;
		if (needsProfile) {
			payload.nik = form.nik; payload.gender = form.gender; payload.birth_date = form.birth_date; payload.address = form.address;
		}
		try {
			const res = await api.post('/visits', payload);
			goto('/app/antrian/' + res.data.visit.id);
		} catch (err) {
			errors = err.errors ?? {}; error = err.errors ? '' : err.message || 'Gagal mendaftar kunjungan';
		} finally { submitting = false; }
	}
</script>

<div class="mx-auto max-w-3xl">
	<h1 class="text-xl font-bold text-slate-800">Pendaftaran Kunjungan</h1>
	<p class="mb-5 text-sm text-slate-500">Ikuti 3 langkah sesuai alur klinik.</p>
	<div class="mb-6 flex items-center gap-2">
		{#each [{ n: 1, label: 'Pilih Poli' }, { n: 2, label: 'Pilih Dokter' }, { n: 3, label: 'Konfirmasi' }] as s}
			<div class="flex items-center gap-2">
				<div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ring-1 {step === s.n ? 'bg-teal-600 text-white ring-teal-600' : step > s.n ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-white text-slate-400 ring-slate-200'}">
					{#if step > s.n}✓{:else}{s.n}{/if}
				</div>
				<span class="hidden text-sm font-medium sm:inline {step >= s.n ? 'text-slate-800' : 'text-slate-400'}">{s.label}</span>
				<span class="text-sm font-medium sm:hidden {step >= s.n ? 'text-slate-800' : 'text-slate-400'}">{s.n}</span>
			</div>
			{#if s.n < 3}<div class="h-px flex-1 bg-slate-200"></div>{/if}
		{/each}
	</div>
	{#if loading}
		<p class="rounded-xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if step === 1}
		<p class="mb-3 text-sm font-semibold text-slate-700">1 — Pilih Poli</p>
		<div class="space-y-2">
			{#each polyclinics as poli}
				{@const m = metaFor(poli.name)}
				<button onclick={() => pilihPoli(poli)} class="flex w-full items-center gap-3 rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:border-teal-300 hover:shadow {selectedPoli?.id === poli.id ? 'border-teal-400 bg-teal-50/60' : 'border-slate-200'}">
					<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-lg text-white {m.bg}">{m.icon}</span>
					<div class="min-w-0 flex-1">
						<p class="text-sm font-semibold text-slate-800">{poli.name}</p>
						<p class="text-xs text-slate-500">{poli.description ?? 'Pelayanan spesialis'} · {poli.doctors_count ?? 0} dokter</p>
					</div>
					<span class="text-slate-400">›</span>
				</button>
			{/each}
		</div>
	{:else if step === 2}
		<button onclick={() => (selectedPoli = null)} class="mb-3 text-sm font-medium text-teal-700 hover:underline">← Ganti poli</button>
		<p class="mb-3 text-sm font-semibold text-slate-700">2 — Pilih Dokter — {selectedPoli.name}</p>
		{#if doctors.length === 0}
			<div class="rounded-xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Belum ada dokter di poli ini. <button onclick={() => (selectedPoli = null)} class="text-teal-700 hover:underline">Pilih poli lain</button></div>
		{:else}
			<div class="space-y-3">
				{#each doctors as doctor}
					<button onclick={() => pilihDokter(doctor)} class="flex w-full items-center gap-3 rounded-2xl border bg-white p-4 text-left shadow-sm transition hover:border-teal-300 {selectedDoctor?.id === doctor.id ? 'border-teal-400 bg-teal-50/60' : 'border-slate-200'}">
						<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 font-semibold text-teal-700">{doctor.user.name.charAt(0)}</div>
						<div class="min-w-0 flex-1">
							<p class="text-sm font-semibold text-slate-800">{doctor.user.name}</p>
							<p class="text-xs text-slate-500">{doctor.specialization} · {doctor.polyclinic?.name ?? selectedPoli.name}</p>
						</div>
						{#if selectedDoctor?.id === doctor.id}<span class="text-sm font-bold text-teal-600">✓ Dipilih</span>{:else}<span class="text-xs text-slate-400">Pilih</span>{/if}
					</button>
				{/each}
			</div>
			{#if selectedDoctor}
				<div class="mt-4 rounded-2xl border bg-white p-4 shadow-sm">
					<p class="text-sm font-semibold text-slate-800">Jadwal praktik {selectedDoctor.user.name}</p>
					{#if schedules.length === 0}
						<p class="mt-2 text-xs text-slate-500">Jadwal belum diatur admin — tetap bisa mendaftar untuk hari ini.</p>
					{:else}
						<div class="mt-3 grid gap-2 sm:grid-cols-2">
							{#each schedules as j}
								<button onclick={() => (selectedSchedule = j)} class="rounded-xl border px-3 py-2.5 text-left transition {selectedSchedule?.id === j.id ? 'border-teal-400 bg-teal-50' : 'border-slate-200 bg-slate-50 hover:border-teal-200'}">
									<p class="text-sm font-medium text-slate-800">{hariLabel[j.day]}</p>
									<p class="text-xs text-slate-500">{j.start_time.slice(0,5)} — {j.end_time.slice(0,5)} · kuota {j.quota}</p>
								</button>
							{/each}
						</div>
						<p class="mt-2 text-xs text-slate-400">Pilih jadwal atau <button onclick={() => (selectedSchedule = null)} class="font-medium text-teal-700 hover:underline">tanpa jadwal</button>.</p>
					{/if}
				</div>
			{/if}
		{/if}
	{:else}
		<button onclick={() => (selectedDoctor = null)} class="mb-3 text-sm font-medium text-teal-700 hover:underline">← Ganti dokter</button>
		<div class="rounded-2xl border bg-white p-5 shadow-sm">
			<h2 class="text-sm font-bold text-slate-800">3 — Konfirmasi</h2>
			<div class="mt-3 grid gap-2 rounded-xl bg-slate-50 p-3 text-sm">
				<p><span class="text-slate-500">Poli:</span> <span class="font-medium text-slate-800">{selectedPoli.name}</span> <span class="ml-2 rounded bg-white px-1.5 py-0.5 text-xs ring-1 ring-slate-200">{selectedPoli.queue_code ?? ''}</span></p>
				<p><span class="text-slate-500">Dokter:</span> <span class="font-medium text-slate-800">{selectedDoctor.user.name}</span> <span class="text-slate-500">· {selectedDoctor.specialization}</span></p>
				{#if selectedSchedule}<p><span class="text-slate-500">Jadwal:</span> <span class="font-medium text-slate-800">{hariLabel[selectedSchedule.day]} {selectedSchedule.start_time.slice(0,5)}–{selectedSchedule.end_time.slice(0,5)}</span></p>{:else}<p class="text-xs text-slate-500">Tanpa jadwal spesifik — kunjungan hari ini</p>{/if}
			</div>
			{#if !needsProfile && patientData}
				<div class="rounded-xl border border-teal-200 bg-teal-50 p-3">
					<p class="flex items-center gap-2 text-sm font-semibold text-teal-800">✓ Data Anda sudah tersimpan</p>
					<p class="mt-1 text-xs leading-relaxed text-teal-700">NIK, jenis kelamin, tanggal lahir, dan alamat sudah terisi dari kunjungan pertama. Tidak perlu input ulang — cukup isi keluhan.</p>
					<div class="mt-2 grid gap-1.5 rounded-lg bg-white p-3 text-xs ring-1 ring-teal-100">
						<p><span class="text-slate-500">NIK:</span> <span class="font-mono font-medium text-slate-800">{patientData.nik}</span></p>
						<p><span class="text-slate-500">Jenis Kelamin:</span> <span class="font-medium text-slate-800">{patientData.gender === 'L' ? 'Laki-laki' : 'Perempuan'}</span> · <span class="text-slate-500">Tgl Lahir:</span> {String(patientData.birth_date).slice(0,10)}</p>
						<p><span class="text-slate-500">Alamat:</span> <span class="font-medium text-slate-800">{patientData.address}</span> · <span class="text-slate-500">HP:</span> {patientData.phone}</p>
					</div>
					<p class="mt-2 text-xs text-slate-500">Perlu ubah? Edit di <a href="/app/profil" class="font-medium text-teal-700 hover:underline">Profil Saya</a>.</p>
				</div>
				{/if}

			<form onsubmit={daftar} class="mt-5 space-y-4">
				{#if needsProfile}
					<div class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">Lengkapi data diri. Cukup sekali.</div>
					<div><label class="mb-1 block text-sm font-medium" for="nik">NIK (16 digit)</label><input id="nik" bind:value={form.nik} maxlength="16" inputmode="numeric" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none" placeholder="3578xxxxxxxxxxxx" />{#if errors.nik}<p class="mt-1 text-xs text-red-600">{errors.nik[0]}</p>{/if}</div>
					<div class="grid grid-cols-2 gap-3">
						<div><label class="mb-1 block text-sm font-medium" for="gender">Jenis Kelamin</label><select id="gender" bind:value={form.gender} class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
						<div><label class="mb-1 block text-sm font-medium" for="birth_date">Tanggal Lahir</label><input id="birth_date" type="date" bind:value={form.birth_date} required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" />{#if errors.birth_date}<p class="mt-1 text-xs text-red-600">{errors.birth_date[0]}</p>{/if}</div>
					</div>
					<div><label class="mb-1 block text-sm font-medium" for="address">Alamat</label><textarea id="address" bind:value={form.address} required rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="Jl. ..."></textarea>{#if errors.address}<p class="mt-1 text-xs text-red-600">{errors.address[0]}</p>{/if}</div>
				{/if}
				<div><label class="mb-1 block text-sm font-medium" for="complaint">Keluhan <span class="font-normal text-slate-400">— jelas dan singkat</span></label><textarea id="complaint" bind:value={form.complaint} required rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="Contoh: Demam 2 hari, batuk pilek..."></textarea>{#if errors.complaint}<p class="mt-1 text-xs text-red-600">{errors.complaint[0]}</p>{/if}</div>
				{#if error}<p class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-200">{error}</p>{/if}
				<button type="submit" disabled={submitting} class="w-full rounded-xl bg-teal-600 py-3 text-sm font-semibold text-white shadow hover:bg-teal-700 disabled:opacity-50">{submitting ? 'Mendaftarkan...' : 'Daftar Kunjungan'}</button>
				<p class="text-center text-xs text-slate-400">Nomor antrian terbit otomatis (A-001, reset harian per poli)</p>
			</form>
		</div>
	{/if}
</div>
