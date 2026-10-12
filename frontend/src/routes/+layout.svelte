<script>
	import './layout.css';
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { api } from '$lib/api.js';
	import { auth } from '$lib/auth.svelte.js';

	let { children } = $props();

	// Di dalam /app, tampilan diatur oleh layout app (sidebar per peran)
	const inApp = $derived(page.url.pathname.startsWith('/app'));

	function handleLogout() {
		api.post('/logout', {}).catch(() => {});
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
			pasien: '/app/pasien'
		}[auth.user?.role] ?? '/'
	);
</script>

{#if inApp}
	{@render children()}
{:else}
	<div class="flex min-h-screen flex-col">
		<!-- Header -->
		<header class="border-b border-gray-100 bg-white">
			<nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
				<!-- Logo -->
				<a href="/" class="flex items-center gap-2.5">
					<span
						class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-600 text-[18px] leading-none text-white shadow-sm"
						aria-hidden="true">♥</span
					>
					<span class="text-[18px] font-bold tracking-tight text-teal-700">SistemKlinik</span>
				</a>

				<!-- Center Nav -->
				<div class="hidden items-center gap-8 text-[14px] font-medium text-gray-700 md:flex">
					<a href="/layanan" class="transition hover:text-teal-700">Layanan</a>
					<a href="/dokter" class="transition hover:text-teal-700">Dokter</a>
					<a href="/poli" class="transition hover:text-teal-700">Poli Kami</a>
				</div>

				<!-- Right Auth -->
				<div class="flex items-center gap-3">
					{#if auth.user}
						<a
							href={dashboardPath}
							class="hidden text-sm font-medium text-teal-700 hover:underline sm:inline"
						>
							{auth.user.name}
						</a>
						<button
							onclick={handleLogout}
							class="rounded-full border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
						>
							Keluar
						</button>
					{:else}
						<a
							href="/login"
							class="hidden text-sm font-medium text-gray-700 transition hover:text-teal-700 sm:inline"
							>Masuk</a
						>
						<a
							href="/register"
							class="rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
						>
							Daftar
						</a>
					{/if}
				</div>
			</nav>
			<!-- Mobile nav -->
			<div class="flex justify-center gap-6 border-t border-gray-100 py-2.5 text-sm font-medium text-gray-600 md:hidden">
				<a href="/layanan" class="hover:text-teal-700">Layanan</a>
				<a href="/dokter" class="hover:text-teal-700">Dokter</a>
				<a href="/poli" class="hover:text-teal-700">Poli Kami</a>
			</div>
		</header>

		<main class="flex-1 bg-[#F0FAF9]">
			{@render children()}
		</main>

		<!-- Footer -->
		<footer class="border-t border-gray-100 bg-white">
			<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
				<!-- 5 Feature Icons -->
				<div class="flex flex-wrap items-start justify-center gap-6 sm:gap-8 lg:gap-12">
					<!-- Mudah Digunakan -->
					<div class="flex w-[96px] flex-col items-center gap-2.5 text-center">
						<div
							class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 ring-1 ring-teal-100"
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="1.7"
								stroke-linecap="round"
								stroke-linejoin="round"
								class="h-6 w-6 text-teal-600"
								aria-hidden="true"
							>
								<path d="M12 21s-6.5-4.2-8.5-9.2A4.8 4.8 0 0 1 8 5.2a4.5 4.5 0 0 1 4 2 4.5 4.5 0 0 1 4-2 4.8 4.8 0 0 1 4.5 6.6C18.5 16.8 12 21 12 21Z" />
								<path d="M9 11l2 2 4-4" />
							</svg>
						</div>
						<span class="text-xs font-medium leading-tight text-gray-700">Mudah<br />Digunakan</span>
					</div>

					<!-- Akses Mudah & Cepat -->
					<div class="flex w-[96px] flex-col items-center gap-2.5 text-center">
						<div
							class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 ring-1 ring-teal-100"
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="1.7"
								stroke-linecap="round"
								stroke-linejoin="round"
								class="h-6 w-6 text-teal-600"
								aria-hidden="true"
							>
								<path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" />
							</svg>
						</div>
						<span class="text-xs font-medium leading-tight text-gray-700"
							>Akses Mudah<br />& Cepat</span
						>
					</div>

					<!-- Navigasi Konsisten -->
					<div class="flex w-[96px] flex-col items-center gap-2.5 text-center">
						<div
							class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 ring-1 ring-teal-100"
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="1.7"
								stroke-linecap="round"
								stroke-linejoin="round"
								class="h-6 w-6 text-teal-600"
								aria-hidden="true"
							>
								<circle cx="12" cy="12" r="8" />
								<path d="M12 8v8M8 12h8" />
							</svg>
						</div>
						<span class="text-xs font-medium leading-tight text-gray-700"
							>Navigasi<br />Konsisten</span
						>
					</div>

					<!-- Kontras Warna Tinggi -->
					<div class="flex w-[96px] flex-col items-center gap-2.5 text-center">
						<div
							class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 ring-1 ring-teal-100"
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="1.7"
								stroke-linecap="round"
								stroke-linejoin="round"
								class="h-6 w-6 text-teal-600"
								aria-hidden="true"
							>
								<circle cx="12" cy="12" r="8" />
								<path d="M12 4a8 8 0 0 1 0 16V4z" fill="currentColor" stroke="none" />
								<circle cx="12" cy="12" r="8" />
							</svg>
						</div>
						<span class="text-xs font-medium leading-tight text-gray-700"
							>Kontras Warna<br />Tinggi</span
						>
					</div>

					<!-- Tombol Besar -->
					<div class="flex w-[96px] flex-col items-center gap-2.5 text-center">
						<div
							class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 ring-1 ring-teal-100"
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="1.7"
								stroke-linecap="round"
								stroke-linejoin="round"
								class="h-6 w-6 text-teal-600"
								aria-hidden="true"
							>
								<rect x="4" y="7" width="16" height="10" rx="5" />
								<circle cx="9.5" cy="12" r="1.8" fill="currentColor" stroke="none" />
								<circle cx="14.5" cy="12" r="1.8" fill="currentColor" stroke="none" />
							</svg>
						</div>
						<span class="text-xs font-medium leading-tight text-gray-700">Tombol<br />Besar</span>
					</div>
				</div>

				<!-- Tagline cursive -->
				<p
					class="mt-8 text-center text-[22px] leading-none text-teal-700"
					style="font-family: 'Segoe Script','Brush Script MT','Comic Sans MS',cursive; font-style: italic;"
				>
					Sehat Bersama, Lebih Mudah
				</p>
				<p class="mt-2 text-center text-xs text-gray-400">© {new Date().getFullYear()} SistemKlinik</p>
			</div>
		</footer>
	</div>
{/if}
