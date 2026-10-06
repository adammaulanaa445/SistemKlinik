import { PUBLIC_API_URL } from '$env/static/public';
import { auth } from './auth.svelte.js';
import { goto } from '$app/navigation';
import { browser } from '$app/environment';

async function request(method, path, body) {
	const headers = { 'Content-Type': 'application/json' };
	if (auth.token) headers['Authorization'] = 'Bearer ' + auth.token;
	const response = await fetch(PUBLIC_API_URL + path, { method, headers, body: body ? JSON.stringify(body) : undefined });
	const data = await response.json().catch(() => ({}));
	if (!response.ok) {
		if (response.status === 401) { auth.clear(); if (browser) goto('/login'); }
		throw { status: response.status, ...data };
	}
	return data;
}
export const api = { get: (p) => request('GET', p), post: (p,b) => request('POST', p, b), put: (p,b) => request('PUT', p, b), patch: (p,b) => request('PATCH', p, b), delete: (p) => request('DELETE', p) };
