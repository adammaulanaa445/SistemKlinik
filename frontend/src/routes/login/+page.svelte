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
			goto('/');
		} catch (err) {
			error = err.message || 'Email atau password salah';
		} finally {
			loading = false;
		}
	}
</script>

<div class="mx-auto mt-16 max-w-sm rounded-xl border p-6 shadow-sm">
	<h1 class="mb-4 text-xl font-semibold">Masuk</h1>

	<form onsubmit={handleLogin} class="space-y-3">
		<div>
			<label class="mb-1 block text-sm" for="email">Email</label>
			<input
				id="email"
				type="email"
				bind:value={email}
				required
				class="w-full rounded-lg border px-3 py-2"
			/>
		</div>

		<div>
			<label class="mb-1 block text-sm" for="password">Password</label>
			<input
				id="password"
				type="password"
				bind:value={password}
				required
				class="w-full rounded-lg border px-3 py-2"
			/>
		</div>

		{#if error}
			<p class="text-sm text-red-600">{error}</p>
		{/if}

		<button
			type="submit"
			disabled={loading}
			class="w-full rounded-lg bg-teal-600 py-2 text-white hover:bg-teal-700 disabled:opacity-50"
		>
			{loading ? 'Memproses...' : 'Masuk'}
		</button>
	</form>
</div>