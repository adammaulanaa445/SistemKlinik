<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { auth, requireRole } from '$lib/auth.svelte.js';

	let doctor = $state(null);
	let schedules = $state([]);
	let loading = $state(true);
	let error = $state('');

	const hariLabel = {
		senin: 'Senin',
		selasa: 'Selasa',
		rabu: 'Rabu',
		kamis: 'Kamis',
		jumat: 'Jumat',
		sabtu: 'Sabtu',
		minggu: 'Minggu'
	};
	const hariOrder = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
	const hariColor = {
		senin: 'bg-sky-50 ring-sky-200',
		selasa: 'bg-emerald-50 ring-emerald-200',
		rabu: 'bg-teal-50 ring-teal-200',
		kamis: 'bg-amber-50 ring-amber-200',
		jumat: 'bg-indigo-50 ring-indigo-200',
		sabtu: 'bg-orange-50 ring-orange-200',
		minggu: 'bg-rose-50 ring-rose-200'
	};

	const sorted = $derived([...schedules].sort((a, b) => hariOrder.indexOf(a.day) - hariOrder.indexOf(b.day)));

	onMount(async () => {
		if (!requireRole('dokter')) return;
		try {
			let doctorId = null;
			// try /me first — preferred path
			try {
				const me = await api.get('/me');
				// me may be { id, ... , patient, doctor } or wrapped in data
				const payload = me.data ?? me;
				doctorId = payload.doctor?.id ?? payload.doctor_id ?? null;
				// some backends nest user.doctor
				if (!doctorId && payload.user?.doctor?.id) doctorId = payload.user.doctor.id;
				if (!doctorId && auth.user?.doctor?.id) doctorId = auth.user.doctor.id;
			} catch (_) {}
			// fallback: scan /doctors and match by user_id / user.id
			if (!doctorId) {
				try {
					const res = await api.get('/doctors');
					const list = res.data ?? res ?? [];
					const uid = auth.user?.id;
					const found = list.find((d) => d.user_id === uid || d.user?.id === uid);
					if (found) doctorId = found.id;
				} catch (_) {}
			}
			if (!doctorId) {
				error = 'Data dokter tidak ditemukan untuk akun ini.';
				return;
			}
			const res = await api.get('/doctors/' + doctorId);
			doctor = res.data ?? res;
			schedules = doctor.schedules ?? [];
		} catch (err) {
			error = err.message || 'Gagal memuat jadwal';
		} finally {
			loading = false;
		}
	});
</script>

<div class="mx-auto max-w-4xl">
	<h1 class="text-xl font-bold text-slate-800">Jadwal Praktik Saya</h1>
	<p class="mb-5 text-sm text-slate-500">Jadwal mingguan Anda di klinik — hubungi admin untuk ubah.</p>

	{#if loading}
		<p class="rounded-xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat jadwal...</p>
	{:else if error}
		<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>
	{:else}
		{#if doctor}
			<div class="mb-4 rounded-2xl border bg-white p-4 shadow-sm">
				<div class="flex items-center gap-3">
					<div class="flex h-11 w-11 items-center justify-center rounded-full bg-teal-100 font-bold text-teal-700">
						{doctor.user?.name?.charAt(0) ?? '?'}
					</div>
					<div>
						<p class="text-sm font-semibold text-slate-800">{doctor.user?.name ?? auth.user?.name}</p>
						<p class="text-xs text-slate-500">{doctor.specialization} · {doctor.polyclinic?.name ?? '-'} · {doctor.doctor_code}</p>
					</div>
				</div>
			</div>
		{/if}

		{#if sorted.length === 0}
			<div class="rounded-2xl border bg-white p-8 text-center shadow-sm">
				<p class="text-sm font-medium text-slate-600">Belum ada jadwal</p>
				<p class="mt-1 text-xs text-slate-500">Jadwal praktik Anda belum diatur. Silakan hubungi admin untuk penambahan jadwal.</p>
			</div>
		{:else}
			<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
				{#each sorted as j}
					<div class="rounded-2xl border bg-white p-4 shadow-sm ring-1 {hariColor[j.day] ?? 'bg-slate-50 ring-slate-200'}">
						<div class="flex items-center justify-between">
							<span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{hariLabel[j.day] ?? j.day}</span>
							<span class="text-xs text-slate-400">kuota {j.quota}</span>
						</div>
						<p class="mt-3 text-lg font-bold text-slate-800">{j.start_time.slice(0,5)} — {j.end_time.slice(0,5)}</p>
						<p class="text-xs text-slate-500">Jam praktik · {hariLabel[j.day] ?? j.day}</p>
						{#if j.polyclinic?.name}
							<p class="mt-2 text-xs text-slate-400">{j.polyclinic.name}</p>
						{/if}
					</div>
				{/each}
			</div>
		{/if}

		<div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
			<span class="font-semibold">Catatan:</span> Hubungi admin untuk ubah jadwal, kuota, atau jam praktik.
		</div>
	{/if}
</div>
