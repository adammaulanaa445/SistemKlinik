$base = "http://localhost:8000/api"

function Login($email) {
    $body = @{ email = $email; password = "password123" } | ConvertTo-Json
    $r = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body $body
    return @{ Authorization = "Bearer $($r.token)" }
}

try {
    # 1. Pasien daftar lagi (nomor harus naik, misal A-002)
    $pasien = Login "budi@example.com"
    $visitBody = @{ doctor_id = 1; complaint = "Batuk dan pilek" } | ConvertTo-Json
    $v = Invoke-RestMethod -Uri "$base/visits" -Method Post -ContentType "application/json" -Headers $pasien -Body $visitBody
    Write-Host "Nomor antrian baru: $($v.data.queue.queue_number)"

    # 2. Petugas lihat antrian hari ini
    $petugas = Login "petugas@klinik.test"
    $list = Invoke-RestMethod -Uri "$base/queues/today" -Method Get -Headers $petugas
    Write-Host "Antrian hari ini:"
    $list.data | ForEach-Object { Write-Host "  $($_.queue_number) - $($_.status)" }

    # 3. Dokter panggil antrian pertama, lalu mulai periksa
    $dokter = Login "dokter@klinik.test"
    $first = $list.data[0].id

    $c = Invoke-RestMethod -Uri "$base/queues/$first/call" -Method Patch -Headers $dokter
    Write-Host "Dipanggil: $($c.data.queue_number) -> $($c.data.status)"

    $s = Invoke-RestMethod -Uri "$base/queues/$first/start" -Method Patch -Headers $dokter
    Write-Host "Diperiksa: $($s.data.queue_number) -> $($s.data.status)"

    # 4. Pasien tidak boleh melihat daftar antrian
    try {
        Invoke-RestMethod -Uri "$base/queues/today" -Method Get -Headers $pasien
        Write-Host "SALAH: pasien seharusnya ditolak"
    }
    catch {
        Write-Host "Pasien ditolak (403), sudah benar."
    }
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}