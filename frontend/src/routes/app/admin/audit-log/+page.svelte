<script>
	import { onMount } from 'svelte';
	import { api } from '$lib/api.js';
	import { requireRole } from '$lib/auth.svelte.js';

	let logs = $state([]);
	let loading = $state(true);
	let error = $state('');

	onMount(async () => {
		if (!requireRole('admin')) return;
		try {
			const res = await api.get('/audit-logs');
			logs = res.data ?? [];
		} catch (e) {
			error = e.message || 'Gagal memuat audit log';
		} finally {
			loading = false;
		}
	});
</script>

<div class="mx-auto max-w-5xl">
	<h1 class="text-xl font-bold tracking-tight text-slate-900">Audit Log</h1>
	<p class="mb-5 text-sm text-slate-500">Aktivitas pengguna di sistem.</p>

	{#if loading}
		<p class="rounded-2xl border bg-white p-6 text-center text-sm text-slate-500 shadow-sm">Memuat...</p>
	{:else if error}
		<p class="rounded-2xl border bg-red-50 p-6 text-center text-sm text-red-600 shadow-sm">{error}</p>
	{:else}
		<div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="bg-slate-50">
						<tr>
							<th class="px-4 py-3 text-left">Waktu</th>
							<th class="px-4 py-3 text-left">User</th>
							<th class="px-4 py-3 text-left">Aksi</th>
							<th class="px-4 py-3 text-left">Modul</th>
							<th class="px-4 py-3 text-left">Deskripsi</th>
						</tr>
					</thead>
					<tbody>
						{#each logs as l}
							<tr class="border-t">
								<td class="px-4 py-3 text-xs text-slate-500">{new Date(l.created_at).toLocaleString('id-ID')}</td>
								<td class="px-4 py-3">{l.user?.name ?? '-'}</td>
								<td class="px-4 py-3 font-mono text-xs">{l.action}</td>
								<td class="px-4 py-3">{l.module}</td>
								<td class="px-4 py-3 text-xs text-slate-600">{l.description ?? '-'}</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
</div>
