<section class="mx-auto max-w-6xl px-4 py-16">
	<div class="grid items-center gap-10 md:grid-cols-2">
		<div>
			<h1 class="text-3xl font-bold text-gray-900 md:text-4xl">
				Pelayanan Kesehatan yang Lebih Mudah dan Teratur
			</h1>
			<p class="mt-4 text-gray-600">
				Daftar kunjungan, pantau antrian, dan kelola layanan kesehatan Anda dalam satu sistem.
			</p>
			<div class="mt-6 flex gap-3">
				<a
					href="/register"
					class="rounded-lg bg-teal-600 px-5 py-2.5 text-white hover:bg-teal-700"
				>
					Buat Akun
				</a>
				<a href="/layanan" class="rounded-lg border px-5 py-2.5 hover:bg-gray-50">
					Lihat Layanan
				</a>
			</div>
		</div>
		<div class="flex justify-center">
			<div class="h-64 w-full rounded-2xl bg-teal-100"></div>
		</div>
	</div>

	<div class="mt-16 grid grid-cols-2 gap-4 md:grid-cols-4">
		{#each [['6+', 'Poli'], ['10+', 'Dokter'], ['5', 'Layanan'], ['24/7', 'Online']] as [angka, label]}
			<div class="rounded-xl border bg-white p-4 text-center">
				<p class="text-2xl font-bold text-teal-700">{angka}</p>
				<p class="text-sm text-gray-500">{label}</p>
			</div>
		{/each}
	</div>
</section>