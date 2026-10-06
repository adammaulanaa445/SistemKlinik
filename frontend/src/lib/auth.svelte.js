import { browser } from '$app/environment';
import { goto } from '$app/navigation';

function createAuth() {
	let user = $state(null);
	let token = $state(null);
	if (browser) {
		try {
			const savedToken = localStorage.getItem('token');
			const savedUser = localStorage.getItem('user');
			if (savedToken && savedUser) {
				token = savedToken;
				user = JSON.parse(savedUser);
			}
		} catch {
			localStorage.removeItem('token');
			localStorage.removeItem('user');
		}
	}
	return {
		get user() { return user; },
		get token() { return token; },
		set(newUser, newToken) {
			user = newUser;
			token = newToken;
			if (browser) {
				localStorage.setItem('token', newToken);
				localStorage.setItem('user', JSON.stringify(newUser));
			}
		},
		clear() {
			user = null;
			token = null;
			if (browser) {
				localStorage.removeItem('token');
				localStorage.removeItem('user');
			}
		}
	};
}
export const auth = createAuth();

export function requireRole(...allowedRoles) {
	if (!auth.user) {
		if (browser) goto('/login');
		return false;
	}
	if (!allowedRoles.includes(auth.user.role)) {
		if (browser) goto('/');
		return false;
	}
	return true;
}
