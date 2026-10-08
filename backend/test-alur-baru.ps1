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
    $petugas = Login "petugas@klinik.test"
    $dokter  = Login "dokter@klinik.test"
    $farmasi = Login "farmasi@klinik.test"

    Write-Host "== 1. Register (akun + no. HP saja) =="
    $email = "uji$(Get-Date -Format 'HHmmss')@example.com"
    $reg = Kirim Post "/register" @{} @{
        name = "Pasien Uji"; email = $email; phone = "081200000000"
        password = "password123"; password_confirmation = "password123"
    }
    $pasien = @{ Authorization = "Bearer $($reg.token)" }
    Write-Host "Akun dibuat: $($reg.user.name), role $($reg.user.role)"

    Write-Host ""
    Write-Host "== 2. Kunjungan pertama =="
    try {
        Kirim Post "/visits" $pasien @{ doctor_id = 1; complaint = "Batuk" } | Out-Null
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Ditolak karena data pasien belum lengkap, sudah benar." }

    $nik = "3578{0}000" -f (Get-Random -Minimum 100000000 -Maximum 999999999)
    $v1 = Kirim Post "/visits" $pasien @{
        doctor_id = 1; complaint = "Batuk"
        nik = $nik; gender = "L"; birth_date = "1995-03-20"; address = "Jl. Uji No. 1"
    }
    Write-Host "Data pasien tersimpan, antrian: $($v1.data.queue.queue_number)"

    Write-Host ""
    Write-Host "== 3. Kunjungan kedua (data pasien dipakai ulang) =="
    $v2 = Kirim Post "/visits" $pasien @{ doctor_id = 1; complaint = "Kontrol" }
    Write-Host "Berhasil tanpa isi data lagi, antrian: $($v2.data.queue.queue_number)"

    Write-Host ""
    Write-Host "== 4. Petugas memanggil, dokter memeriksa =="
    $c = Kirim Patch "/queues/$($v1.data.queue.id)/call" $petugas
    Write-Host "Petugas memanggil: $($c.data.queue_number) -> $($c.data.status)"
    $s = Kirim Patch "/queues/$($v1.data.queue.id)/start" $dokter
    Write-Host "Dokter mulai periksa: $($s.data.queue_number) -> $($s.data.status)"

    $rm = Kirim Post "/medical-records" $dokter @{
        visit_id = $v1.data.visit.id
        examination_result = "Tenggorokan merah"
        diagnosis = "Faringitis"
        prescription_items = @( @{ medicine_id = 1; quantity = 2; dosage = "3x sehari 1 tablet" } )
    }
    Write-Host "Rekam medis disimpan, status resep: $($rm.data.prescription.status)"

    Write-Host ""
    Write-Host "== 5. Farmasi memproses resep =="
    $rid = $rm.data.prescription.id
    Kirim Patch "/prescriptions/$rid/process" $farmasi | Out-Null
    $done = Kirim Patch "/prescriptions/$rid/complete" $farmasi
    Write-Host "Resep: $($done.data.status)"

    Write-Host ""
    Write-Host "== 6. Tagihan otomatis =="
    $list = Kirim Get "/payments?status=belum_bayar" $petugas
    $tag = $list.data | Where-Object { $_.visit_id -eq $v1.data.visit.id }
    Write-Host "Tagihan muncul otomatis: $($tag.payment_code) - Rp $($tag.amount)"
    $p = Kirim Patch "/payments/$($tag.id)/pay" $petugas @{ method = "qris" }
    Write-Host "Dibayar: $($p.data.status)"

    Write-Host ""
    Write-Host "== 7. Kunjungan tanpa resep =="
    Kirim Patch "/queues/$($v2.data.queue.id)/call" $petugas | Out-Null
    Kirim Patch "/queues/$($v2.data.queue.id)/start" $dokter | Out-Null
    Kirim Post "/medical-records" $dokter @{
        visit_id = $v2.data.visit.id; examination_result = "Membaik"; diagnosis = "Kontrol"
    } | Out-Null
    $list2 = Kirim Get "/payments?status=belum_bayar" $petugas
    $tag2 = $list2.data | Where-Object { $_.visit_id -eq $v2.data.visit.id }
    Write-Host "Tagihan otomatis (tanpa obat): $($tag2.payment_code) - Rp $($tag2.amount)"

    Write-Host ""
    Write-Host "== 8. Logout mencabut token =="
    Kirim Post "/logout" $pasien | Out-Null
    try {
        Kirim Get "/me" $pasien | Out-Null
        Write-Host "SALAH: token seharusnya sudah tidak berlaku"
    }
    catch { Write-Host "Token lama ditolak (401), sudah benar." }
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}