<script>
	import { goto } from '$app/navigation';
	import { api } from '$lib/api.js';
	import { auth } from '$lib/auth.svelte.js';

	let form = $state({
		name: '',
		email: '',
		password: '',
		password_confirmation: '',
		nik: '',
		gender: 'L',
		birth_date: '',
		phone: '',
		address: ''
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
			if (err.errors) {
				errors = err.errors;
			} else {
				errors = { general: [err.message || 'Registrasi gagal'] };
			}
		} finally {
			loading = false;
		}
	}
</script>

<div class="mx-auto my-10 max-w-lg rounded-xl border bg-white p-6 shadow-sm">
	<h1 class="mb-1 text-xl font-semibold">Daftar Akun Pasien</h1>
	<p class="mb-5 text-sm text-gray-500">Lengkapi data diri Anda untuk mulai menggunakan layanan.</p>

	<form onsubmit={handleRegister} class="space-y-4">
		<div>
			<label class="mb-1 block text-sm" for="name">Nama Lengkap</label>
			<input id="name" bind:value={form.name} required class="w-full rounded-lg border px-3 py-2" />
			{#if errors.name}<p class="mt-1 text-xs text-red-600">{errors.name[0]}</p>{/if}
		</div>

		<div class="grid grid-cols-2 gap-3">
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
		</div>

		<div class="grid grid-cols-2 gap-3">
			<div>
				<label class="mb-1 block text-sm" for="password">Password</label>
				<input id="password" type="password" bind:value={form.password} required class="w-full rounded-lg border px-3 py-2" />
				{#if errors.password}<p class="mt-1 text-xs text-red-600">{errors.password[0]}</p>{/if}
			</div>
			<div>
				<label class="mb-1 block text-sm" for="password_confirmation">Konfirmasi Password</label>
				<input id="password_confirmation" type="password" bind:value={form.password_confirmation} required class="w-full rounded-lg border px-3 py-2" />
			</div>
		</div>

		<div class="grid grid-cols-2 gap-3">
			<div>
				<label class="mb-1 block text-sm" for="nik">NIK</label>
				<input id="nik" bind:value={form.nik} maxlength="16" required class="w-full rounded-lg border px-3 py-2" />
				{#if errors.nik}<p class="mt-1 text-xs text-red-600">{errors.nik[0]}</p>{/if}
			</div>
			<div>
				<label class="mb-1 block text-sm" for="gender">Jenis Kelamin</label>
				<select id="gender" bind:value={form.gender} class="w-full rounded-lg border px-3 py-2">
					<option value="L">Laki-laki</option>
					<option value="P">Perempuan</option>
				</select>
			</div>
		</div>

		<div>
			<label class="mb-1 block text-sm" for="birth_date">Tanggal Lahir</label>
			<input id="birth_date" type="date" bind:value={form.birth_date} required class="w-full rounded-lg border px-3 py-2" />
			{#if errors.birth_date}<p class="mt-1 text-xs text-red-600">{errors.birth_date[0]}</p>{/if}
		</div>

		<div>
			<label class="mb-1 block text-sm" for="address">Alamat</label>
			<textarea id="address" bind:value={form.address} required rows="2" class="w-full rounded-lg border px-3 py-2"></textarea>
			{#if errors.address}<p class="mt-1 text-xs text-red-600">{errors.address[0]}</p>{/if}
		</div>

		{#if errors.general}
			<p class="text-sm text-red-600">{errors.general[0]}</p>
		{/if}

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