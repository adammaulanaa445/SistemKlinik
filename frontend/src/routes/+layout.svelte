<script>
	import './layout.css';
	import { goto } from '$app/navigation';
	import { auth } from '$lib/auth.svelte.js';

	let { children } = $props();

	function handleLogout() {
		auth.clear();
		goto('/login');
	}

	// Arahkan ke dashboard sesuai role masing-masing
	const dashboardPath = $derived(
		{
			admin: '/app/admin',
			petugas: '/app/petugas',
			dokter: '/app/dokter',
			farmasi: '/app/farmasi',
			pasien: '/app/kunjungan'
		}[auth.user?.role] ?? '/'
	);
</script>

<div class="flex min-h-screen flex-col">
	<header class="border-b bg-white">
		<nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
			<a href="/" class="text-lg font-bold text-teal-700">SistemKlinik</a>

			<div class="hidden gap-6 text-sm text-gray-600 md:flex">
				<a href="/layanan" class="hover:text-teal-700">Layanan</a>
				<a href="/dokter" class="hover:text-teal-700">Dokter</a>
				<a href="/poli" class="hover:text-teal-700">Poli Kami</a>
			</div>

			<div class="flex items-center gap-3">
				{#if auth.user}
					<a href={dashboardPath} class="text-sm font-medium text-teal-700 hover:underline">
						{auth.user.name}
					</a>
					<button
						onclick={handleLogout}
						class="rounded-lg border px-3 py-1.5 text-sm hover:bg-gray-50"
					>
						Keluar
					</button>
				{:else}
					<a href="/login" class="text-sm font-medium hover:text-teal-700">Masuk</a>
					<a
						href="/register"
						class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm text-white hover:bg-teal-700"
					>
						Daftar
					</a>
				{/if}
			</div>
		</nav>
	</header>

	<main class="flex-1 bg-gray-50">
		{@render children()}
	</main>

	<footer class="border-t bg-white py-6 text-center text-sm text-gray-500">
		SistemKlinik &mdash; Sehat Bersama, Lebih Mudah
	</footer>
</div>