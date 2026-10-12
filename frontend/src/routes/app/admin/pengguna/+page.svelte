<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole, auth } from '$lib/auth.svelte.js';

	let users = $state([]);
	let loading = $state(true);
	let error = $state('');
	let saving = $state(false);
	let filterRole = $state('semua');

	let form = $state({ name: '', email: '', password: '', role: 'petugas' });
	let editingId = $state(null);
	let formErrors = $state({});
	let showForm = $state(false);

	const roles = ['admin', 'petugas', 'farmasi'];
	const roleLabel = { admin: 'Admin', petugas: 'Petugas', farmasi: 'Farmasi' };
	const roleColor = { admin: 'bg-purple-100 text-purple-700', petugas: 'bg-teal-100 text-teal-700', farmasi: 'bg-amber-100 text-amber-700' };

	onMount(async () => {
		if (!requireRole('admin')) return;
		await load();
		loading = false;
	});

	async function load() {
		error = '';
		try {
			const res = await api.get('/users');
			users = res.data;
		} catch (e) {
			error = e.message || 'Gagal memuat pengguna';
		}
	}

	const filtered = $derived(filterRole === 'semua' ? users : users.filter((u) => u.role === filterRole));

	function resetForm() {
		form = { name: '', email: '', password: '', role: 'petugas' };
		editingId = null;
		formErrors = {};
		showForm = false;
	}
	function editUser(u) {
		editingId = u.id;
		form = { name: u.name, email: u.email, password: '', role: u.role };
		formErrors = {};
		showForm = true;
	}
	async function submit(e) {
		e.preventDefault();
		saving = true;
		formErrors = {};
		error = '';
		try {
			if (editingId) {
				const payload = { name: form.name, email: form.email, role: form.role };
				if (form.password) payload.password = form.password;
				await api.put(`/users/${editingId}`, payload);
			} else {
				await api.post('/users', form);
			}
			resetForm();
			await load();
		} catch (err) {
			if (err.errors) formErrors = err.errors;
			else error = err.message || 'Gagal menyimpan pengguna';
		} finally { saving = false; }
	}
	async function removeUser(id) {
		if (!confirm('Hapus pengguna ini?')) return;
		try {
			await api.delete(`/users/${id}`);
			await load();
		} catch (err) {
			alert(err.message || 'Gagal menghapus pengguna');
		}
	}
</script>

<h1 class="text-xl font-bold text-gray-800">Pengguna</h1>
<p class="mb-4 text-sm text-gray-500">Kelola akun <code>admin / petugas / farmasi</code> via <code>/users</code>.</p>

<div class="mb-4 flex flex-wrap items-center gap-2">
	{#each ['semua', ...roles] as r}
		<button onclick={() => (filterRole = r)} class="rounded-full border px-3 py-1.5 text-xs capitalize {filterRole === r ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-gray-600 hover:bg-gray-50'}">{r === 'semua' ? 'Semua' : roleLabel[r]}</button>
	{/each}
	<button onclick={load} class="ml-2 text-sm text-teal-700 hover:underline">Muat ulang</button>
	<button onclick={() => (showForm = !showForm)} class="ml-auto rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">{showForm ? 'Tutup Form' : '+ Tambah Pengguna'}</button>
</div>

{#if loading}
	<p class="text-sm text-gray-500">Memuat...</p>
{:else}
	{#if error}<p class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</p>{/if}

	{#if showForm}
		<form onsubmit={submit} class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
			<h2 class="mb-3 font-semibold text-gray-800">{editingId ? 'Edit Pengguna' : 'Tambah Pengguna'}</h2>
			<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
				<div>
					<label class="text-xs font-medium text-gray-600">Nama *</label>
					<input bind:value={form.name} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
					{#if formErrors.name}<p class="mt-1 text-xs text-red-600">{formErrors.name[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Email *</label>
					<input type="email" bind:value={form.email} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
					{#if formErrors.email}<p class="mt-1 text-xs text-red-600">{formErrors.email[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Password {editingId ? '(kosongkan jika tidak ganti)' : '*'}</label>
					<input type="password" bind:value={form.password} placeholder={editingId ? 'Kosongkan = tidak ganti' : 'Min 8 karakter'} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
					{#if formErrors.password}<p class="mt-1 text-xs text-red-600">{formErrors.password[0]}</p>{/if}
				</div>
				<div>
					<label class="text-xs font-medium text-gray-600">Role *</label>
					<select bind:value={form.role} class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
						{#each roles as r}<option value={r}>{roleLabel[r]}</option>{/each}
					</select>
					{#if formErrors.role}<p class="mt-1 text-xs text-red-600">{formErrors.role[0]}</p>{/if}
				</div>
			</div>
			<div class="mt-3 flex gap-2">
				<button type="submit" disabled={saving} class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50">{saving ? 'Menyimpan...' : editingId ? 'Simpan Perubahan' : 'Tambah Pengguna'}</button>
				<button type="button" onclick={resetForm} class="rounded-lg border px-4 py-2 text-sm hover:bg-gray-50">Batal</button>
			</div>
		</form>
	{/if}

	<div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
		<table class="w-full text-left text-sm">
			<thead class="bg-gray-50 text-xs text-gray-500">
				<tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Dibuat</th><th class="px-4 py-3">Aksi</th></tr>
			</thead>
			<tbody>
				{#each filtered as u}
					<tr class="border-t hover:bg-gray-50">
						<td class="px-4 py-3 font-medium text-gray-800">{u.name} {#if u.id === auth.user?.id}<span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500">Anda</span>{/if}</td>
						<td class="px-4 py-3 text-xs text-gray-600">{u.email}</td>
						<td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium {roleColor[u.role] ?? 'bg-gray-100 text-gray-600'}">{roleLabel[u.role] ?? u.role}</span></td>
						<td class="px-4 py-3 text-xs text-gray-500">{u.created_at ? new Date(u.created_at).toLocaleDateString('id-ID') : '-'}</td>
						<td class="px-4 py-3">
							<div class="flex gap-2">
								<button onclick={() => editUser(u)} class="rounded border px-2 py-1 text-xs hover:bg-gray-50">Edit</button>
								<button onclick={() => removeUser(u.id)} disabled={u.id === auth.user?.id} class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50 disabled:opacity-40">Hapus</button>
							</div>
						</td>
					</tr>
				{/each}
				{#if filtered.length === 0}<tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada pengguna.</td></tr>{/if}
			</tbody>
		</table>
	</div>
	<p class="mt-2 text-xs text-gray-500">Total: {filtered.length} pengguna{filterRole !== 'semua' ? ` (${filterRole})` : ''} · Keseluruhan: {users.length}</p>
{/if}
