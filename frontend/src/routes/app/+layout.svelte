<script>
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { auth } from '$lib/auth.svelte.js';

	let { children } = $props();
	let checked = $state(false);

	onMount(() => {
		if (!auth.token) {
			goto('/login');
		} else {
			checked = true;
		}
	});
</script>

{#if checked}
	<div class="mx-auto max-w-6xl px-4 py-8">
		{@render children()}
	</div>
{/if}