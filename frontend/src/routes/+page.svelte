<script>
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { auth } from '$lib/auth.svelte.js';
	import { api } from '$lib/api.js';
	const staffDest = { admin: '/app/admin', petugas: '/app/petugas', dokter: '/app/dokter', farmasi: '/app/farmasi' };
	let jadwalToday = $state(null);
	let jadwalLoading = $state(true);
	onMount(async () => {
		try { const res = await api.get('/schedules/today'); jadwalToday = res; } catch {}
		jadwalLoading = false;
		const r = auth.user?.role;
		if (r && staffDest[r]) goto(staffDest[r]);
	});
	function handleDaftar() {
		const r = auth.user?.role;
		if (!r) goto('/register');
		else if (r === 'pasien') goto('/app/kunjungan');
		else goto(staffDest[r]);
	}
</script>

<div class="min-h-screen bg-[#F0FDFA] bg-gradient-to-b from-[#ECFEFF] to-[#F0FDFA]">
	<!-- HERO -->
	<section class="mx-auto max-w-6xl px-4 pt-10 pb-6 md:px-6 md:pt-14">
		<div class="grid items-center gap-8 md:grid-cols-2 md:gap-10">
			<!-- Left: copy -->
			<div>
				<p class="mb-3 inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold tracking-wide text-teal-700 ring-1 ring-teal-100">
					<span class="h-2 w-2 rounded-full bg-teal-500"></span>
					Sistem Klinik Terpadu
				</p>
				<h1 class="text-[28px] font-extrabold leading-[1.15] tracking-tight text-slate-900 md:text-[40px]">
					Pelayanan Kesehatan <br />
					<span class="text-teal-600">yang Lebih Mudah</span><br />
					dan Teratur
				</h1>
				<p class="mt-4 max-w-xl text-[15px] leading-relaxed text-slate-500 md:text-base">
					Daftar kunjungan, pantau antrian, dan kelola layanan kesehatan Anda dalam satu sistem.
					Cepat, aman, dan dapat diakses kapan saja.
				</p>
				<div class="mt-7 flex flex-wrap gap-3">
					<button
						onclick={handleDaftar}
						class="cursor-pointer inline-flex items-center justify-center gap-2 rounded-full bg-teal-600 px-7 py-3 text-sm font-semibold text-white shadow-sm shadow-teal-200 transition hover:bg-teal-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2"
					>
						Daftar Kunjungan
						<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
					</button>
					<a
						href="/layanan"
						class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-7 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
					>
						Lihat Layanan
					</a>
				</div>
				<div class="mt-8 flex items-center gap-3 text-xs text-slate-500">
					<div class="flex -space-x-2">
						<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-teal-600 text-[10px] font-bold text-white ring-2 ring-white">A</span>
						<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-cyan-500 text-[10px] font-bold text-white ring-2 ring-white">B</span>
						<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-300 text-[10px] font-bold text-slate-700 ring-2 ring-white">+9</span>
					</div>
					<span>Dipercaya 1.200+ pasien bulan ini</span>
					<span class="hidden items-center gap-1 sm:inline-flex">
						<span class="h-1 w-1 rounded-full bg-emerald-500"></span> Online
					</span>
				</div>
			</div>

			<!-- Right: image card -->
			<div class="relative flex justify-center md:justify-end">
				<div class="absolute -top-4 -right-4 h-72 w-72 rounded-full bg-teal-100/60 blur-2xl md:h-80 md:w-80"></div>
				<div class="relative w-full max-w-[420px]">
					<div class="overflow-hidden rounded-[28px] bg-white p-2 shadow-xl shadow-teal-100 ring-1 ring-slate-100">
						<div class="relative flex h-[300px] flex-col items-center justify-center overflow-hidden rounded-[20px] bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-600 p-6 text-white md:h-[360px]">
							<div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10"></div>
							<div class="absolute -bottom-12 -left-12 h-56 w-56 rounded-full bg-white/10"></div>
							<div class="absolute top-10 left-10 h-20 w-20 rounded-full border border-white/15"></div>
							<div class="relative flex flex-col items-center text-center">
								<div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 backdrop-blur ring-1 ring-white/20">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-10 w-10">
										<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
										<circle cx="9" cy="7" r="4" />
										<path d="M16 11a4 4 0 0 1 3.5 2.1 4 4 0 0 1 .5 1.9v1" />
										<circle cx="16" cy="7" r="3" />
									</svg>
								</div>
								<p class="mt-4 text-sm font-semibold tracking-wide text-white/90">Foto Dokter & Pasien Lansia</p>
								<p class="mt-1 max-w-[260px] text-xs leading-relaxed text-white/75">
									Ilustrasi pelayanan ramah untuk semua usia
								</p>
								<div class="mt-5 flex gap-2">
									<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-teal-700">Ramah Lansia</span>
									<span class="rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/20">Siaga 24/7</span>
								</div>
							</div>
							<div class="absolute bottom-3 left-3 right-3 flex items-center gap-3 rounded-2xl bg-white p-3 text-slate-700 shadow-lg">
								<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-600 text-white">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
								</div>
								<div>
									<p class="text-xs font-bold leading-none">Antrian Real-time</p>
									<p class="text-xs text-slate-500">No. 12 — Estimasi 15 menit</p>
								</div>
								<span class="ml-auto h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-500"></span>
							</div>
						</div>
					</div>
					<div class="absolute -bottom-4 -left-2 hidden items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-lg ring-1 ring-slate-100 md:flex">
						<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-400 text-white">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M8 2v3"/><path d="M16 2v3"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>
						</div>
						<div>
							<p class="text-xs font-bold text-slate-800">Jadwal Hari Ini</p>
							<p class="text-xs text-slate-500">{jadwalLoading ? 'Memuat...' : ((jadwalToday?.doctors_count ?? 0) + ' Dokter bertugas')}</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- 4 stat cards -->
		<div class="mt-10 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
			<div class="rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm">
				<p class="text-2xl font-extrabold tracking-tight text-teal-600 md:text-[26px]">6+</p>
				<p class="text-sm font-semibold text-slate-800">Poli</p>
				<p class="mt-0.5 text-xs text-slate-400">Spesialis tersedia</p>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm">
				<p class="text-2xl font-extrabold tracking-tight text-teal-600 md:text-[26px]">10+</p>
				<p class="text-sm font-semibold text-slate-800">Dokter</p>
				<p class="mt-0.5 text-xs text-slate-400">Tenaga medis</p>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm">
				<p class="text-2xl font-extrabold tracking-tight text-teal-600 md:text-[26px]">5</p>
				<p class="text-sm font-semibold text-slate-800">Layanan</p>
				<p class="mt-0.5 text-xs text-slate-400">Unggulan</p>
			</div>
			<div class="rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm">
				<p class="text-2xl font-extrabold tracking-tight text-teal-600 md:text-[26px]">24/7</p>
				<p class="text-sm font-semibold text-slate-800">Online</p>
				<p class="mt-0.5 text-xs text-slate-400">Akses kapan saja</p>
			</div>
		</div>
	</section>

	<!-- LAYANAN KAMI -->
	<section class="mx-auto max-w-6xl px-4 pb-14 md:px-6">
		<div class="rounded-[28px] bg-white px-5 py-8 shadow-sm ring-1 ring-slate-100 md:px-8 md:py-10">
			<div class="text-center">
				<h2 class="text-xl font-extrabold tracking-tight text-slate-900 md:text-2xl">Layanan Kami</h2>
				<p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-slate-500">
					Enam layanan utama untuk memenuhi kebutuhan kesehatan Anda sekeluarga
				</p>
				<div class="mx-auto mt-3 h-1 w-12 rounded-full bg-teal-500"></div>
			</div>
			<div class="mt-8 grid grid-cols-3 gap-6 md:grid-cols-6 md:gap-4">
				<div class="flex flex-col items-center text-center">
					<div class="flex h-[72px] w-[72px] items-center justify-center rounded-full bg-teal-600 shadow-md shadow-teal-100 ring-4 ring-teal-50 md:h-20 md:w-20">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M14.5 6.5a2.5 2.5 0 1 1-3.5 3.5L6 15v3h3l5-5a2.5 2.5 0 0 1 0-3.5z"/><path d="M13 7l3 3"/></svg>
					</div>
					<p class="mt-3 text-xs font-semibold leading-tight text-slate-800 md:text-sm">Pemeriksaan<br/>Umum</p>
				</div>
				<div class="flex flex-col items-center text-center">
					<div class="flex h-[72px] w-[72px] items-center justify-center rounded-full bg-teal-600 shadow-md shadow-teal-100 ring-4 ring-teal-50 md:h-20 md:w-20">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11a3 3 0 1 0-6 0 3 3 0 0 0 6 0z"/><path d="M22 11h-3"/><path d="M20.5 9.5V11"/><path d="M19 12.5V11"/></svg>
					</div>
					<p class="mt-3 text-xs font-semibold leading-tight text-slate-800 md:text-sm">Konsultasi<br/>Dokter</p>
				</div>
				<div class="flex flex-col items-center text-center">
					<div class="flex h-[72px] w-[72px] items-center justify-center rounded-full bg-teal-600 shadow-md shadow-teal-100 ring-4 ring-teal-50 md:h-20 md:w-20">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M10 2v8l-3-3"/><path d="M14 2v8l3-3"/><path d="M10 10H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-3"/><path d="M12 14v6"/><path d="M9 17h6"/></svg>
					</div>
					<p class="mt-3 text-xs font-semibold leading-tight text-slate-800 md:text-sm">Farmasi</p>
				</div>
				<div class="flex flex-col items-center text-center">
					<div class="flex h-[72px] w-[72px] items-center justify-center rounded-full bg-teal-600 shadow-md shadow-teal-100 ring-4 ring-teal-50 md:h-20 md:w-20">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
					</div>
					<p class="mt-3 text-xs font-semibold leading-tight text-slate-800 md:text-sm">Rekam Medis</p>
				</div>
				<div class="flex flex-col items-center text-center">
					<div class="flex h-[72px] w-[72px] items-center justify-center rounded-full bg-teal-600 shadow-md shadow-teal-100 ring-4 ring-teal-50 md:h-20 md:w-20">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
					</div>
					<p class="mt-3 text-xs font-semibold leading-tight text-slate-800 md:text-sm">Poli</p>
				</div>
				<div class="flex flex-col items-center text-center">
					<div class="flex h-[72px] w-[72px] items-center justify-center rounded-full bg-teal-600 shadow-md shadow-teal-100 ring-4 ring-teal-50 md:h-20 md:w-20">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
					</div>
					<p class="mt-3 text-xs font-semibold leading-tight text-slate-800 md:text-sm">Jadwal Dokter</p>
				</div>
			</div>
			<div class="mt-8 flex justify-center">
				<a href="/layanan" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
					Lihat Semua Layanan
					<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
				</a>
			</div>
		</div>
	</section>
</div>
