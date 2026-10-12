<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let activeTab = $state('poli');

	let polyclinics = $state([]);
	let doctors = $state([]);
	let schedules = $state([]);
	let loading = $state(true);
	let error = $state('');
	let saving = $state(false);

	// poli
	let poliForm = $state({ name: '', queue_code: '', icon: '', description: '' });
	let editingPoliId = $state(null);
	let poliErrors = $state({});

	// dokter
	let doctorForm = $state({ name: '', email: '', password: '', polyclinic_id: '', doctor_code: '', specialization: '', phone: '' });
	let editingDoctorId = $state(null);
	let doctorErrors = $state({});
	let showDoctorForm = $state(false);

	// jadwal
	let scheduleForm = $state({ doctor_id: '', day: 'senin', start_time: '08:00', end_time: '12:00', quota: 20 });
	let editingScheduleId = $state(null);
	let scheduleErrors = $state({});
	const hariOptions = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];
	const hariLabel = { senin:'Senin', selasa:'Selasa', rabu:'Rabu', kamis:'Kamis', jumat:'Jumat', sabtu:'Sabtu', minggu:'Minggu' };

	onMount(async () => {
		if (!requireRole('admin')) return;
		await loadAll();
		loading = false;
	});

	async function loadAll() {
		error = '';
		try {
			const [p, d, s] = await Promise.all([api.get('/polyclinics'), api.get('/doctors'), api.get('/schedules')]);
			polyclinics = p.data;
			doctors = d.data;
			schedules = s.data ?? s ?? [];
		} catch (e) {
			error = e.message || 'Gagal memuat data master';
		}
	}

	// --- POLI ---
	function resetPoliForm() {
		poliForm = { name: '', queue_code: '', icon: '', description: '' };
		editingPoliId = null;
		poliErrors = {};
	}
	function editPoli(item) {
		editingPoliId = item.id;
		poliForm = { name: item.name, queue_code: item.queue_code, icon: item.icon ?? '', description: item.description ?? '' };
		poliErrors = {};
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}
	async function submitPoli(e) {
		e.preventDefault();
		saving = true; poliErrors = {}; error = '';
		try {
			if (editingPoliId) await api.put(`/polyclinics/${editingPoliId}`, { ...poliForm, queue_code: poliForm.queue_code.toUpperCase() });
			else await api.post('/polyclinics', { ...poliForm, queue_code: poliForm.queue_code.toUpperCase() });
			resetPoliForm(); await loadAll();
		} catch (err) {
			if (err.errors) poliErrors = err.errors; else error = err.message || 'Gagal menyimpan poli';
		} finally { saving = false; }
	}
	async function deletePoli(id) {
		if (!confirm('Hapus poli ini?')) return;
		try { await api.delete(`/polyclinics/${id}`); await loadAll(); } catch (err) { alert(err.message || 'Gagal hapus'); }
	}

	// --- DOKTER ---
	function resetDoctorForm() {
		doctorForm = { name: '', email: '', password: '', polyclinic_id: '', doctor_code: '', specialization: '', phone: '' };
		editingDoctorId = null; doctorErrors = {}; showDoctorForm = false;
	}
	function editDoctor(d) {
		editingDoctorId = d.id;
		doctorForm = {
			name: d.user?.name ?? '', email: d.user?.email ?? '', password: '',
			polyclinic_id: String(d.polyclinic_id ?? ''), doctor_code: d.doctor_code, specialization: d.specialization, phone: d.phone
		};
		doctorErrors = {}; showDoctorForm = true;
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}
	async function submitDoctor(e) {
		e.preventDefault();
		saving = true; doctorErrors = {}; error = '';
		try {
			const payload = { ...doctorForm, polyclinic_id: Number(doctorForm.polyclinic_id) };
			if (editingDoctorId) {
				if (!payload.password) delete payload.password;
				await api.put(`/doctors/${editingDoctorId}`, payload);
			} else await api.post('/doctors', payload);
			resetDoctorForm(); await loadAll();
		} catch (err) {
			if (err.errors) doctorErrors = err.errors; else error = err.message || 'Gagal menyimpan dokter';
		} finally { saving = false; }
	}
	async function deleteDoctor(id) {
		if (!confirm('Hapus dokter ini?')) return;
		try { await api.delete(`/doctors/${id}`); await loadAll(); } catch (err) { alert(err.message || 'Gagal hapus'); }
	}

	// --- JADWAL ---
	function resetScheduleForm() {
		scheduleForm = { doctor_id: '', day: 'senin', start_time: '08:00', end_time: '12:00', quota: 20 };
		editingScheduleId = null; scheduleErrors = {};
	}
	function editSchedule(sc) {
		editingScheduleId = sc.id;
		scheduleForm = { doctor_id: String(sc.doctor_id), day: sc.day, start_time: sc.start_time.slice(0,5), end_time: sc.end_time.slice(0,5), quota: sc.quota };
		scheduleErrors = {};
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}
	async function submitSchedule(e) {
		e.preventDefault();
		saving = true; scheduleErrors = {}; error = '';
		try {
			const payload = { doctor_id: Number(scheduleForm.doctor_id), day: scheduleForm.day, start_time: scheduleForm.start_time, end_time: scheduleForm.end_time, quota: Number(scheduleForm.quota) };
			if (editingScheduleId) await api.put(`/schedules/${editingScheduleId}`, payload);
			else await api.post('/schedules', payload);
			resetScheduleForm(); await loadAll();
		} catch (err) {
			if (err.errors) scheduleErrors = err.errors; else error = err.message || 'Gagal menyimpan jadwal';
		} finally { saving = false; }
	}
	async function deleteSchedule(id) {
		if (!confirm('Hapus jadwal ini?')) return;
		try { await api.delete(`/schedules/${id}`); await loadAll(); } catch (err) { alert(err.message || 'Gagal hapus jadwal'); }
	}
</script>

<h1 class="text-xl font-bold text-gray-800">Data Master</h1>
<p class="mb-4 text-sm text-gray-500">Kelola poli, dokter, dan jadwal praktik klinik.</p>

<div class="mb-4 flex gap-2 border-b">
	<button onclick={() => (activeTab = 'poli')} class="px-4 py-2 text-sm {activeTab === 'poli' ? 'border-b-2 border-teal-600 font-medium text-teal-700' : 'text-gray-500'}">Poli ({polyclinics.length})</button>
	<button onclick={() => (activeTab = 'dokter')} class="px-4 py-2 text-sm {activeTab === 'dokter' ? 'border-b-2 border-teal-600 font-medium text-teal-700' : 'text-gray-500'}">Dokter ({doctors.length})</button>
	<button onclick={() => (activeTab = 'jadwal')} class="px-4 py-2 text-sm {activeTab === 'jadwal' ? 'border-b-2 border-teal-600 font-medium text-teal-700' : 'text-gray-500'}">Jadwal ({schedules.length})</button>
	<button onclick={loadAll} class="ml-auto text-sm text-teal-700 hover:underline">Muat ulang</button>
</div>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else}
	{#if error}<p class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</p>{/if}

	{#if activeTab === 'poli'}
		<form onsubmit={submitPoli} class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="mb-3 font-semibold text-gray-800">{editingPoliId ? 'Edit Poli' : 'Tambah Poli'}</h2>
			<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
				<div>
					<label class="text-xs font-medium text-gray-600">Nama Poli *</label>
					<input bind:value={poliForm.name} placeholder="Contoh: Poli Umum" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
					{#if poliErrors.name}<p class="mt-1 text-xs text-red-600">{poliErrors.name[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Kode Antrian * (contoh: A)</label>
					<input bind:value={poliForm.queue_code} placeholder="A" maxlength="5" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm uppercase" />
					{#if poliErrors.queue_code}<p class="mt-1 text-xs text-red-600">{poliErrors.queue_code[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Icon (emoji)</label>
					<input bind:value={poliForm.icon} placeholder="🩺" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
				</div>
				<div class="sm:col-span-2">
					<label class="text-xs font-medium text-gray-600">Deskripsi</label>
					<textarea bind:value={poliForm.description} rows="2" placeholder="Deskripsi singkat" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></textarea>
				</div>
			</div>
			<div class="mt-3 flex gap-2">
				<button type="submit" disabled={saving} class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">{saving ? 'Menyimpan...' : editingPoliId ? 'Simpan Perubahan' : 'Tambah Poli'}</button>
				{#if editingPoliId}<button type="button" onclick={resetPoliForm} class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Batal</button>{/if}
			</div>
		</form>
		<div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
			<table class="w-full text-left text-sm">
				<thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-3">Poli</th><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Icon</th><th class="px-4 py-3">Dokter</th><th class="px-4 py-3">Aksi</th></tr></thead>
				<tbody>
					{#each polyclinics as item}
						<tr class="border-t">
							<td class="px-4 py-3"><p class="font-medium text-gray-800">{item.name}</p><p class="text-xs text-gray-500">{item.description ?? '-'}</p></td>
							<td class="px-4 py-3"><span class="rounded bg-gray-100 px-2 py-1 font-mono text-xs">{item.queue_code}</span></td>
							<td class="px-4 py-3">{item.icon ?? '-'}</td>
							<td class="px-4 py-3 text-center">{item.doctors_count ?? '-'}</td>
							<td class="px-4 py-3"><div class="flex gap-2"><button onclick={() => editPoli(item)} class="rounded border px-2 py-1 text-xs hover:bg-gray-50">Edit</button><button onclick={() => deletePoli(item.id)} class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Hapus</button></div></td>
						</tr>
					{/each}
					{#if polyclinics.length === 0}<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada poli.</td></tr>{/if}
				</tbody>
			</table>
		</div>

	{:else if activeTab === 'dokter'}
		<div class="mb-4 flex justify-end">
			<button onclick={() => (showDoctorForm = !showDoctorForm)} class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">{showDoctorForm ? 'Tutup Form' : '+ Tambah Dokter'}</button>
		</div>
		{#if showDoctorForm}
			<form onsubmit={submitDoctor} class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
				<h2 class="mb-3 font-semibold text-gray-800">{editingDoctorId ? 'Edit Dokter' : 'Tambah Dokter'}</h2>
				<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
					<div><label class="text-xs font-medium text-gray-600">Nama *</label><input bind:value={doctorForm.name} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />{#if doctorErrors.name}<p class="mt-1 text-xs text-red-600">{doctorErrors.name[0]}</p>{/if}</div>
					<div><label class="text-xs font-medium text-gray-600">Email *</label><input type="email" bind:value={doctorForm.email} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />{#if doctorErrors.email}<p class="mt-1 text-xs text-red-600">{doctorErrors.email[0]}</p>{/if}</div>
					<div><label class="text-xs font-medium text-gray-600">Password {editingDoctorId ? '(kosongkan jika tidak ganti)' : '*'}</label><input type="password" bind:value={doctorForm.password} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />{#if doctorErrors.password}<p class="mt-1 text-xs text-red-600">{doctorErrors.password[0]}</p>{/if}</div>
					<div><label class="text-xs font-medium text-gray-600">Poli *</label><select bind:value={doctorForm.polyclinic_id} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="">Pilih Poli</option>{#each polyclinics as item}<option value={item.id}>{item.name} ({item.queue_code})</option>{/each}</select>{#if doctorErrors.polyclinic_id}<p class="mt-1 text-xs text-red-600">{doctorErrors.polyclinic_id[0]}</p>{/if}</div>
					<div><label class="text-xs font-medium text-gray-600">Kode Dokter *</label><input bind:value={doctorForm.doctor_code} placeholder="DOK002" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />{#if doctorErrors.doctor_code}<p class="mt-1 text-xs text-red-600">{doctorErrors.doctor_code[0]}</p>{/if}</div>
					<div><label class="text-xs font-medium text-gray-600">Spesialisasi *</label><input bind:value={doctorForm.specialization} placeholder="Dokter Umum" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />{#if doctorErrors.specialization}<p class="mt-1 text-xs text-red-600">{doctorErrors.specialization[0]}</p>{/if}</div>
					<div><label class="text-xs font-medium text-gray-600">Telepon *</label><input bind:value={doctorForm.phone} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />{#if doctorErrors.phone}<p class="mt-1 text-xs text-red-600">{doctorErrors.phone[0]}</p>{/if}</div>
				</div>
				<div class="mt-3 flex gap-2">
					<button type="submit" disabled={saving} class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">{saving ? 'Menyimpan...' : editingDoctorId ? 'Simpan Perubahan' : 'Tambah Dokter'}</button>
					<button type="button" onclick={resetDoctorForm} class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Batal</button>
				</div>
			</form>
		{/if}
		<div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
			<table class="w-full text-left text-sm">
				<thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-3">Dokter</th><th class="px-4 py-3">Poli</th><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Spesialisasi</th><th class="px-4 py-3">Telepon</th><th class="px-4 py-3">Aksi</th></tr></thead>
				<tbody>
					{#each doctors as d}
						<tr class="border-t">
							<td class="px-4 py-3"><p class="font-medium text-gray-800">{d.user?.name}</p><p class="text-xs text-gray-500">{d.user?.email}</p></td>
							<td class="px-4 py-3">{d.polyclinic?.name ?? '-'}</td>
							<td class="px-4 py-3 font-mono text-xs">{d.doctor_code}</td>
							<td class="px-4 py-3">{d.specialization}</td>
							<td class="px-4 py-3">{d.phone}</td>
							<td class="px-4 py-3"><div class="flex gap-2"><button onclick={() => editDoctor(d)} class="rounded border px-2 py-1 text-xs hover:bg-gray-50">Edit</button><button onclick={() => deleteDoctor(d.id)} class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Hapus</button></div></td>
						</tr>
					{/each}
					{#if doctors.length === 0}<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada dokter.</td></tr>{/if}
				</tbody>
			</table>
		</div>

	{:else if activeTab === 'jadwal'}
		<form onsubmit={submitSchedule} class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="mb-1 font-semibold text-gray-800">{editingScheduleId ? 'Edit Jadwal' : 'Tambah Jadwal Praktik'}</h2>
			<p class="mb-3 text-xs leading-relaxed text-gray-500">Jadwal yang dibuat di sini akan langsung terlihat di <span class="font-medium text-gray-700">Jadwal dokter</span> dan bisa dipilih pasien saat mendaftar (Pilih Dokter → pilih hari & jam). Atur per dokter agar alur klinik sesuai jam praktik nyata.</p>
			<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
				<div>
					<label class="text-xs font-medium text-gray-600">Dokter *</label>
					<select bind:value={scheduleForm.doctor_id} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
						<option value="">Pilih Dokter</option>
						{#each doctors as d}<option value={d.id}>{d.user?.name} — {d.polyclinic?.name} ({d.doctor_code})</option>{/each}
					</select>
					{#if scheduleErrors.doctor_id}<p class="mt-1 text-xs text-red-600">{scheduleErrors.doctor_id[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Hari *</label>
					<select bind:value={scheduleForm.day} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
						{#each hariOptions as h}<option value={h}>{hariLabel[h]}</option>{/each}
					</select>
					{#if scheduleErrors.day}<p class="mt-1 text-xs text-red-600">{scheduleErrors.day[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Jam Mulai *</label>
					<input type="time" bind:value={scheduleForm.start_time} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
					{#if scheduleErrors.start_time}<p class="mt-1 text-xs text-red-600">{scheduleErrors.start_time[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Jam Selesai *</label>
					<input type="time" bind:value={scheduleForm.end_time} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
					{#if scheduleErrors.end_time}<p class="mt-1 text-xs text-red-600">{scheduleErrors.end_time[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Kuota / hari</label>
					<input type="number" min="1" bind:value={scheduleForm.quota} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
					{#if scheduleErrors.quota}<p class="mt-1 text-xs text-red-600">{scheduleErrors.quota[0]}</p>{/if}
				</div>
			</div>
			<div class="mt-3 flex gap-2">
				<button type="submit" disabled={saving} class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">{saving ? 'Menyimpan...' : editingScheduleId ? 'Simpan Perubahan' : 'Tambah Jadwal'}</button>
				{#if editingScheduleId}<button type="button" onclick={resetScheduleForm} class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Batal</button>{/if}
			</div>
		</form>
		<div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
			<table class="w-full text-left text-sm">
				<thead class="bg-gray-50 text-xs text-gray-500"><tr><th class="px-4 py-3">Dokter</th><th class="px-4 py-3">Poli</th><th class="px-4 py-3">Hari</th><th class="px-4 py-3">Jam</th><th class="px-4 py-3">Kuota</th><th class="px-4 py-3">Aksi</th></tr></thead>
				<tbody>
					{#each schedules as sc}
						<tr class="border-t">
							<td class="px-4 py-3 font-medium text-gray-800">{sc.doctor?.user?.name ?? doctors.find(d=>d.id===sc.doctor_id)?.user?.name ?? sc.doctor_id}</td>
							<td class="px-4 py-3 text-xs text-gray-500">{sc.polyclinic?.name ?? doctors.find(d=>d.id===sc.doctor_id)?.polyclinic?.name ?? '-'}</td>
							<td class="px-4 py-3"><span class="rounded bg-teal-50 px-2 py-1 text-xs font-medium text-teal-700">{hariLabel[sc.day] ?? sc.day}</span></td>
							<td class="px-4 py-3 font-mono text-xs">{sc.start_time.slice(0,5)} – {sc.end_time.slice(0,5)}</td>
							<td class="px-4 py-3 text-center text-xs">{sc.quota}</td>
							<td class="px-4 py-3"><div class="flex gap-2"><button onclick={()=>editSchedule(sc)} class="rounded border px-2 py-1 text-xs hover:bg-gray-50">Edit</button><button onclick={()=>deleteSchedule(sc.id)} class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Hapus</button></div></td>
						</tr>
					{/each}
					{#if schedules.length===0}<tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada jadwal. Tambahkan di atas — dokter akan langsung melihatnya di menu Jadwal.</td></tr>{/if}
				</tbody>
			</table>
		</div>
	{/if}
{/if}
