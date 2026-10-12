<script>
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';
	import { auth } from '$lib/auth.svelte.js';

	let email = $state('');
	let password = $state('');
	let error = $state('');
	let loading = $state(false);

	async function handleLogin(e) {
		e.preventDefault();
		error = '';
		loading = true;

		try {
			const res = await api.post('/login', { email, password });
				auth.set(res.user, res.token);
				// Staff langsung ke sidebar, pasien ke dashboard pasien
				const role = res.user.role;
				const dest = {
					admin: '/app/admin',
					petugas: '/app/petugas',
					dokter: '/app/dokter',
					farmasi: '/app/farmasi',
					pasien: '/app/pasien'
				}[role] ?? '/';
				goto(dest);
		} catch (err) {
			error = err.message || 'Email atau password salah';
		} finally {
			loading = false;
		}
	}
</script>

<div class="flex min-h-screen items-center justify-center bg-[#e6f4f2] p-4">
	<div
		class="flex w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-xl max-md:flex-col md:min-h-[560px]"
	>
		<!-- Left: image placeholder -->
		<div
			class="relative flex flex-1 flex-col items-center justify-center bg-gradient-to-br from-teal-500 via-teal-400 to-cyan-500 p-8 text-white md:w-1/2"
		>
			<!-- decorative circles -->
			<div class="absolute -top-10 -left-10 h-40 w-40 rounded-full bg-white/10"></div>
			<div class="absolute -bottom-8 -right-8 h-56 w-56 rounded-full bg-white/10"></div>
			<div class="absolute top-1/2 left-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/5"></div>

			<div class="relative z-10 flex flex-col items-center text-center">
				<!-- Icon / illustration placeholder -->
				<div
					class="mb-6 flex h-48 w-48 items-center justify-center rounded-3xl border-2 border-dashed border-white/40 bg-white/15 backdrop-blur-sm md:h-56 md:w-56"
				>
					<div class="flex flex-col items-center gap-3 px-4">
						<div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-white/90 shadow-lg">
							<svg
								xmlns="http://www.w3.org/2000/svg"
								class="h-10 w-10 text-teal-600"
								fill="none"
								viewBox="0 0 24 24"
								stroke="currentColor"
								stroke-width="1.6"
							>
								<rect x="7" y="2" width="10" height="20" rx="2" />
								<path d="M12 18h.01" stroke-linecap="round" />
								<path d="M8 6a4 4 0 0 1 3-1.2A4 4 0 0 1 14 6" stroke-linecap="round" />
								<circle cx="9" cy="10" r="1.4" fill="currentColor" stroke="none" />
								<circle cx="15" cy="10" r="1.4" fill="currentColor" stroke="none" />
								<path d="M9.5 13.5c1 1 2 1 3 0" stroke-linecap="round" />
							</svg>
						</div>
						<span class="text-xs font-medium tracking-wide text-white/80">Elderly couple + smartphone</span>
						<div class="flex -space-x-2">
							<div
								class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-amber-100 text-xs"
							>
								&#x1F474;
							</div>
							<div
								class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-orange-100 text-xs"
							>
								&#x1F475;
							</div>
						</div>
					</div>
				</div>

				<h2 class="text-2xl font-bold tracking-tight md:text-[26px]">Mudah, Cepat, Aman</h2>
				<p class="mt-2 max-w-[280px] text-sm leading-relaxed text-white/90">
					Layanan kesehatan dalam genggaman Anda. Daftar berobat online kapan saja, di mana saja.
				</p>

				<div class="mt-5 flex gap-2">
					<span class="h-1.5 w-6 rounded-full bg-white"></span>
					<span class="h-1.5 w-1.5 rounded-full bg-white/50"></span>
					<span class="h-1.5 w-1.5 rounded-full bg-white/50"></span>
				</div>
			</div>
		</div>

		<!-- Right: form card -->
		<div class="flex flex-1 flex-col justify-center bg-white p-8 md:w-1/2 md:p-10 lg:p-12">
			<div class="mx-auto w-full max-w-sm">
				<h1 class="text-2xl font-bold text-slate-800">Selamat Datang</h1>
				<p class="mt-1.5 text-sm text-slate-500">Masuk untuk melanjutkan ke Sistem Klinik</p>

				<form onsubmit={handleLogin} class="mt-8 space-y-4">
					<div>
						<label class="mb-1.5 block text-sm font-medium text-slate-700" for="email"
							>Email / No. HP</label
						>
						<div class="relative">
							<span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
								<svg
									xmlns="http://www.w3.org/2000/svg"
									class="h-[18px] w-[18px] text-slate-400"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="1.7"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
									/>
								</svg>
							</span>
							<input
								id="email"
								type="text"
								bind:value={email}
								required
								placeholder="contoh@email.com"
								autocomplete="username"
								class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-3 pl-10 text-sm text-slate-800 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
							/>
						</div>
					</div>

					<div>
						<div class="mb-1.5 flex items-center justify-between">
							<label class="block text-sm font-medium text-slate-700" for="password">Password</label>
							<a href="/forgot-password" class="text-xs font-medium text-teal-600 hover:text-teal-700 hover:underline"
								>Lupa password?</a
							>
						</div>
						<div class="relative">
							<span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
								<svg
									xmlns="http://www.w3.org/2000/svg"
									class="h-[18px] w-[18px] text-slate-400"
									fill="none"
									viewBox="0 0 24 24"
									stroke="currentColor"
									stroke-width="1.7"
								>
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
									/>
								</svg>
							</span>
							<input
								id="password"
								type="password"
								bind:value={password}
								required
								placeholder="••••••••"
								autocomplete="current-password"
								class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-3 pl-10 text-sm text-slate-800 placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
							/>
						</div>
					</div>

					{#if error}
						<p class="rounded-xl bg-red-50 px-3 py-2.5 text-sm text-red-600 ring-1 ring-red-200">{error}</p>
					{/if}

					<button
						type="submit"
						disabled={loading}
						class="mt-2 w-full rounded-xl bg-teal-600 py-3 text-sm font-semibold text-white shadow-md shadow-teal-600/20 transition hover:bg-teal-700 focus:ring-2 focus:ring-teal-500 focus:ring-offset-1 focus:outline-none disabled:opacity-50 disabled:shadow-none"
					>
						{loading ? 'Memproses...' : 'Masuk'}
					</button>
				</form>

				<p class="mt-6 text-center text-sm text-slate-500">
					Belum punya akun?
					<a href="/register" class="font-semibold text-teal-600 hover:text-teal-700 hover:underline">Daftar</a>
				</p>
			</div>
		</div>
	</div>
</div>
