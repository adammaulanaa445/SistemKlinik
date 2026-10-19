<script>
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { api } from '$lib/api.js';
	import { auth } from '$lib/auth.svelte.js';

	let { children } = $props();
	let checked = $state(false);

	const roleLabel = {
		admin: 'Administrator',
		petugas: 'Petugas',
		dokter: 'Dokter',
		farmasi: 'Petugas Farmasi',
		pasien: 'Pasien'
	};

	// href: null = halaman belum dibuat (tampil redup + "segera")
	const menus = {
		pasien: [
			{ label: 'Dashboard', icon: '🏠', href: '/app/pasien' },
			{ label: 'Kunjungan', icon: '📝', href: '/app/kunjungan' },
			{ label: 'Booking', icon: '??', href: '/app/booking' },
			{ label: 'Antrian', icon: '🔢', href: '/app/antrian' },
			{ label: 'Riwayat', icon: '🕘', href: '/app/riwayat' },
			{ label: 'Profil', icon: '👤', href: '/app/profil' }
		],
		dokter: [
			{ label: 'Dashboard', icon: '🏠', href: '/app/dokter', match: ['/app/dokter', '/app/pemeriksaan'] },
			{ label: 'Antrian', icon: '🔢', href: '/app/dokter/antrian' },
			{ label: 'Appointment', icon: '??', href: '/app/appointments' },
			{ label: 'Appointment', icon: '??', href: '/app/appointments' },
			{ label: 'Pasien', icon: '👥', href: '/app/dokter/pasien' },
			{ label: 'Rekam Medis', icon: '📋', href: '/app/dokter/rekam-medis' },
			{ label: 'Jadwal', icon: '📅', href: '/app/dokter/jadwal' }
		],
		petugas: [
			{ label: 'Antrian & Pembayaran', icon: '🏠', href: '/app/petugas' },
			{ label: 'Pendaftaran', icon: '📝', href: '/app/petugas/pendaftaran' },
			{ label: 'Cari Pasien', icon: '🔍', href: '/app/petugas/cari-pasien' },
			{ label: 'Pasien', icon: '👥', href: '/app/petugas/pasien' }
		],
		farmasi: [
			{ label: 'Dashboard', icon: '🏠', href: '/app/farmasi' },
			{ label: 'Stok Obat', icon: '💊', href: '/app/farmasi/stok' }
		],
		admin: [
			{ label: 'Dashboard', icon: '🏠', href: '/app/admin' },
			{ label: 'Antrian & Pembayaran', icon: '🧾', href: '/app/petugas' },
			{ label: 'Resep & Obat', icon: '💊', href: '/app/farmasi' },
			{ label: 'Data Master', icon: '🗂️', href: '/app/admin/master' },
			{ label: 'Kunjungan', icon: '📝', href: '/app/admin/kunjungan' },
			{ label: 'Pasien', icon: '👥', href: '/app/admin/pasien' },
			{ label: 'Laporan', icon: '📊', href: '/app/admin/laporan' },
			{ label: 'Pengguna', icon: '🔑', href: '/app/admin/pengguna' },
			{ label: 'Audit Log', icon: '??', href: '/app/admin/audit-log' },
		]
	};

	const role = $derived(auth.user?.role);
	const menu = $derived(menus[role] ?? []);

	function isActive(item) {
		return (item.match ?? [item.href]).some((p) => page.url.pathname.startsWith(p));
	}

	onMount(() => {
		if (!auth.token) {
			goto('/login');
		} else {
			checked = true;
		}
	});

	function handleLogout() {
		api.post('/logout', {}).catch(() => {});
		auth.clear();
		goto('/login');
	}
</script>

<svelte:head>
	<title>{roleLabel[role] ?? 'Panel'} - SistemKlinik</title>
</svelte:head>

{#if checked}
	<div class="flex min-h-screen flex-col md:flex-row">
		<aside class="flex w-full shrink-0 flex-col bg-teal-900 text-white md:w-60">
			<div class="flex items-center gap-2 px-5 py-5">
				<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-lg">💚</span>
				<span class="text-lg font-bold">SistemKlinik</span>
			</div>

			<div class="mx-4 mb-3 flex items-center gap-3 rounded-xl bg-white/10 p-3">
				<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-500 font-semibold">
					{auth.user?.name?.charAt(0)}
				</div>
				<div class="min-w-0">
					<p class="truncate text-sm font-medium">{auth.user?.name}</p>
					<p class="text-xs text-teal-200">{roleLabel[role]}</p>
				</div>
			</div>

			<nav class="flex-1 space-y-1 px-3">
				{#each menu as item}
					{#if item.href}
						<a
							href={item.href}
							class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {isActive(item)
								? 'bg-teal-600 font-medium'
								: 'text-teal-50 hover:bg-white/10'}"
						>
							<span>{item.icon}</span>{item.label}
						</a>
					{:else}
						<div class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-teal-200/50">
							<span>{item.icon}</span>{item.label}
							<span class="ml-auto rounded bg-white/10 px-1.5 py-0.5 text-[10px]">segera</span>
						</div>
					{/if}
				{/each}
			</nav>

			<div class="p-4">
				<a href="/" class="mb-2 block text-center text-xs text-teal-200 hover:underline">Beranda Website</a>
				<button
					onclick={handleLogout}
					class="flex w-full items-center justify-center gap-2 rounded-lg bg-white/15 py-2 text-sm hover:bg-white/25"
				>
					🚪 Keluar
				</button>
			</div>
		</aside>

		<main class="flex-1 bg-slate-50">
			<div class="mx-auto max-w-5xl px-6 py-8">
				{@render children()}
			</div>
		</main>
	</div>
{/if}
