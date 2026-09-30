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
    $admin  = Login "admin@klinik.test"
    $pasien = Login "budi@example.com"

    Write-Host "== Poli =="
    $poli = Kirim Post "/polyclinics" $admin @{
        name        = "Poli Gigi"
        queue_code  = "b"
        description = "Perawatan gigi dan mulut"
    }
    Write-Host "Poli dibuat: $($poli.data.name), kode $($poli.data.queue_code)"

    try {
        Kirim Post "/polyclinics" $admin @{ name = "Poli Bedah"; queue_code = "B" }
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Kode antrian ganda ditolak, sudah benar." }

    $u = Kirim Put "/polyclinics/$($poli.data.id)" $admin @{ description = "Perawatan gigi, mulut, dan estetika" }
    Write-Host "Poli diubah: $($u.data.description)"

    Write-Host ""
    Write-Host "== Dokter =="
    $dok = Kirim Post "/doctors" $admin @{
        name           = "drg. Sari Wulandari"
        email          = "drg.sari@klinik.test"
        password       = "password123"
        polyclinic_id  = $poli.data.id
        doctor_code    = "DOK002"
        specialization = "Dokter Gigi"
        phone          = "081234567892"
    }
    Write-Host "Dokter dibuat: $($dok.data.user.name) di $($dok.data.polyclinic.name)"

    $loginDok = Login "drg.sari@klinik.test"
    $me = Kirim Get "/me" $loginDok
    Write-Host "Akun dokter baru bisa login, role: $($me.role)"

    Write-Host ""
    Write-Host "== Jadwal =="
    $s1 = Kirim Post "/schedules" $admin @{ doctor_id = $dok.data.id; day = "senin"; start_time = "08:00"; end_time = "12:00"; quota = 20 }
    Write-Host "Jadwal 1 dibuat: $($s1.data.day) $($s1.data.start_time) - $($s1.data.end_time)"

    try {
        Kirim Post "/schedules" $admin @{ doctor_id = $dok.data.id; day = "senin"; start_time = "10:00"; end_time = "14:00" }
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Jadwal bertabrakan ditolak, sudah benar." }

    $s2 = Kirim Post "/schedules" $admin @{ doctor_id = $dok.data.id; day = "senin"; start_time = "12:00"; end_time = "15:00" }
    Write-Host "Jadwal 2 (menyambung jam 12:00) diterima: $($s2.data.start_time) - $($s2.data.end_time)"

    $pub = Invoke-RestMethod -Uri "$base/doctors/$($dok.data.id)" -Method Get
    Write-Host "Jadwal terlihat di halaman publik: $($pub.data.schedules.Count) jadwal"

    Write-Host ""
    Write-Host "== Hak akses =="
    try {
        Kirim Post "/polyclinics" $pasien @{ name = "Poli Palsu"; queue_code = "Z" }
        Write-Host "SALAH: pasien seharusnya ditolak"
    }
    catch { Write-Host "Pasien tidak boleh menambah poli (403), sudah benar." }

    Write-Host ""
    Write-Host "== Hapus =="
    try {
        Kirim Delete "/polyclinics/1" $admin
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Poli Umum tidak bisa dihapus (masih ada dokter dan riwayat), sudah benar." }

    try {
        Kirim Delete "/polyclinics/$($poli.data.id)" $admin
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Poli Gigi tidak bisa dihapus selama masih ada dokter, sudah benar." }

    Kirim Delete "/schedules/$($s1.data.id)" $admin | Out-Null
    Write-Host "Jadwal 1 dihapus"
    Kirim Delete "/doctors/$($dok.data.id)" $admin | Out-Null
    Write-Host "Dokter dihapus"
    Kirim Delete "/polyclinics/$($poli.data.id)" $admin | Out-Null
    Write-Host "Poli Gigi dihapus"

    $list = Invoke-RestMethod -Uri "$base/polyclinics" -Method Get
    Write-Host "Poli tersisa: $($list.data.Count)"
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}