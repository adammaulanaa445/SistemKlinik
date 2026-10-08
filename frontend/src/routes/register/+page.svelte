<script>
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';
	import { auth } from '$lib/auth.svelte.js';

	let form = $state({
		name: '',
		email: '',
		phone: '',
		password: '',
		password_confirmation: ''
	});

	let errors = $state({});
	let loading = $state(false);

	async function handleRegister(e) {
		e.preventDefault();
		errors = {};
		loading = true;

		try {
			const res = await api.post('/register', form);
			auth.set(res.user, res.token);
			goto('/app/kunjungan');
		} catch (err) {
			errors = err.errors ?? { general: [err.message || 'Registrasi gagal'] };
		} finally {
			loading = false;
		}
	}
</script>

<div class="mx-auto my-10 max-w-md rounded-xl border bg-white p-6 shadow-sm">
	<h1 class="mb-1 text-xl font-semibold">Daftar Akun Pasien</h1>
	<p class="mb-5 text-sm text-gray-500">Data diri lengkap diisi sekali saat pendaftaran kunjungan pertama.</p>

	<form onsubmit={handleRegister} class="space-y-4">
		<div>
			<label class="mb-1 block text-sm" for="name">Nama Lengkap</label>
			<input id="name" bind:value={form.name} required class="w-full rounded-lg border px-3 py-2" />
			{#if errors.name}<p class="mt-1 text-xs text-red-600">{errors.name[0]}</p>{/if}
		</div>

		<div>
			<label class="mb-1 block text-sm" for="email">Email</label>
			<input id="email" type="email" bind:value={form.email} required class="w-full rounded-lg border px-3 py-2" />
			{#if errors.email}<p class="mt-1 text-xs text-red-600">{errors.email[0]}</p>{/if}
		</div>

		<div>
			<label class="mb-1 block text-sm" for="phone">No. HP</label>
			<input id="phone" bind:value={form.phone} required class="w-full rounded-lg border px-3 py-2" />
			{#if errors.phone}<p class="mt-1 text-xs text-red-600">{errors.phone[0]}</p>{/if}
		</div>

		<div>
			<label class="mb-1 block text-sm" for="password">Password</label>
			<input id="password" type="password" bind:value={form.password} required class="w-full rounded-lg border px-3 py-2" />
			{#if errors.password}<p class="mt-1 text-xs text-red-600">{errors.password[0]}</p>{/if}
		</div>

		<div>
			<label class="mb-1 block text-sm" for="password_confirmation">Konfirmasi Password</label>
			<input id="password_confirmation" type="password" bind:value={form.password_confirmation} required class="w-full rounded-lg border px-3 py-2" />
		</div>

		{#if errors.general}<p class="text-sm text-red-600">{errors.general[0]}</p>{/if}

		<button
			type="submit"
			disabled={loading}
			class="w-full rounded-lg bg-teal-600 py-2.5 text-white hover:bg-teal-700 disabled:opacity-50"
		>
			{loading ? 'Memproses...' : 'Daftar'}
		</button>

		<p class="text-center text-sm text-gray-500">
			Sudah punya akun? <a href="/login" class="text-teal-700 hover:underline">Masuk</a>
		</p>
	</form>
</div>