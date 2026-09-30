$base = "http://localhost:8000/api"

function Login($email) {
    $body = @{ email = $email; password = "password123" } | ConvertTo-Json
    $r = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body $body
    return @{ Authorization = "Bearer $($r.token)" }
}

function Kirim($method, $path, $headers, $data = $null) {
    if ($data) {
        return Invoke-RestMethod -Uri "$base$path" -Method $method -ContentType "application/json" -Headers $headers -Body ($data | ConvertTo-Json -Depth 5)
    }
    return Invoke-RestMethod -Uri "$base$path" -Method $method -Headers $headers
}

try {
    $admin = Login "admin@klinik.test"

    Write-Host "== Obat =="
    $obat = Kirim Post "/medicines" $admin @{
        code = "OBT005"; name = "Ibuprofen 400mg"; category = "Analgesik"
        unit = "tablet"; stock = 100; price = 2000
    }
    Write-Host "Obat dibuat: $($obat.data.name), status $($obat.data.status)"

    Kirim Delete "/medicines/$($obat.data.id)" $admin | Out-Null
    $listNonaktif = Kirim Get "/medicines" $admin
    $masihTampil = $listNonaktif.data | Where-Object { $_.id -eq $obat.data.id }
    Write-Host "Setelah dinonaktifkan, muncul di daftar aktif? $(if ($masihTampil) { 'YA (salah)' } else { 'TIDAK (benar)' })"

    Kirim Patch "/medicines/$($obat.data.id)/activate" $admin | Out-Null
    $listAktif = Kirim Get "/medicines" $admin
    $tampilLagi = $listAktif.data | Where-Object { $_.id -eq $obat.data.id }
    Write-Host "Setelah diaktifkan lagi, muncul di daftar? $(if ($tampilLagi) { 'YA (benar)' } else { 'TIDAK (salah)' })"

    Write-Host ""
    Write-Host "== Pengguna =="
    $petugasBaru = Kirim Post "/users" $admin @{
        name = "Rina Petugas Baru"; email = "rina.petugas@klinik.test"
        password = "password123"; role = "petugas"
    }
    Write-Host "Petugas baru dibuat: $($petugasBaru.data.name)"

    try {
        Kirim Post "/users" $admin @{ name = "Coba Pasien"; email = "cobapasien@klinik.test"; password = "password123"; role = "pasien" }
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Membuat akun pasien lewat endpoint ini ditolak, sudah benar." }

    $u = Kirim Put "/users/$($petugasBaru.data.id)" $admin @{ name = "Rina P. (Updated)" }
    Write-Host "Diubah: $($u.data.name)"

    try {
        Kirim Delete "/users/$($admin.Authorization -replace '.*','')" $admin
    } catch {}
    # Coba admin hapus dirinya sendiri (pakai id dari /me)
    $meAdmin = Kirim Get "/me" $admin
    try {
        Kirim Delete "/users/$($meAdmin.id)" $admin
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Admin tidak bisa hapus akun sendiri, sudah benar." }

    Kirim Delete "/users/$($petugasBaru.data.id)" $admin | Out-Null
    Write-Host "Petugas baru dihapus"

    Write-Host ""
    Write-Host "== Data Pasien =="
    $pasienList = Kirim Get "/patients" $admin
    Write-Host "Jumlah pasien terdaftar: $($pasienList.data.Count)"

    $p1 = Kirim Get "/patients/1" $admin
    Write-Host "Pasien #1: $($p1.data.user.name), jumlah kunjungan: $($p1.data.visits_count)"

    $pasien = Login "budi@example.com"
    try {
        Kirim Get "/patients" $pasien
        Write-Host "SALAH: pasien seharusnya ditolak"
    }
    catch { Write-Host "Pasien tidak boleh lihat daftar pasien lain (403), sudah benar." }
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}

$farmasi = Login "farmasi@klinik.test"
$obatBaru = Kirim Post "/medicines" $farmasi @{
    code = "OBT006"; name = "Antasida"; category = "Lambung"
    unit = "tablet"; stock = 50; price = 1200
}
Write-Host "Farmasi bisa tambah obat: $($obatBaru.data.name)"