import { PUBLIC_API_URL } from '$env/static/public';
import { auth } from './auth.svelte.js';

async function request(method, path, body) {
	const headers = { 'Content-Type': 'application/json' };

	if (auth.token) {
		headers['Authorization'] = `Bearer ${auth.token}`;
	}

	const response = await fetch(`${PUBLIC_API_URL}${path}`, {
		method,
		headers,
		body: body ? JSON.stringify(body) : undefined
	});

	const data = await response.json().catch(() => ({}));

	if (!response.ok) {
		// Token tidak valid/kedaluwarsa → paksa logout
		if (response.status === 401) {
			auth.clear();
		}
		throw { status: response.status, ...data };
	}

	return data;
}

export const api = {
	get: (path) => request('GET', path),
	post: (path, body) => request('POST', path, body),
	put: (path, body) => request('PUT', path, body),
	patch: (path, body) => request('PATCH', path, body),
	delete: (path) => request('DELETE', path)
};