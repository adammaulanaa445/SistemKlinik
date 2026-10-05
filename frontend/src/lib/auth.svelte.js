function createAuth() {
	let user = $state(null);
	let token = $state(null);

	// Saat aplikasi pertama kali dibuka di browser, ambil sesi tersimpan (kalau ada)
	if (typeof localStorage !== 'undefined') {
		const savedToken = localStorage.getItem('token');
		const savedUser = localStorage.getItem('user');
		if (savedToken && savedUser) {
			token = savedToken;
			user = JSON.parse(savedUser);
		}
	}

	return {
		get user() {
			return user;
		},
		get token() {
			return token;
		},
		set(newUser, newToken) {
			user = newUser;
			token = newToken;
			localStorage.setItem('token', newToken);
			localStorage.setItem('user', JSON.stringify(newUser));
		},
		clear() {
			user = null;
			token = null;
			localStorage.removeItem('token');
			localStorage.removeItem('user');
		}
	};
}

export const auth = createAuth();


import { goto } from '$app/navigation';

export function requireRole(...allowedRoles) {
	if (!auth.user) {
		goto('/login');
		return false;
	}
	if (!allowedRoles.includes(auth.user.role)) {
		goto('/');
		return false;
	}
	return true;
}