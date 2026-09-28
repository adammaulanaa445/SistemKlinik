$base = "http://localhost:8000/api"

function Login($email) {
    $body = @{ email = $email; password = "password123" } | ConvertTo-Json
    $r = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body $body
    return @{ Authorization = "Bearer $($r.token)" }
}

try {
    $petugas = Login "petugas@klinik.test"
    $pasien  = Login "budi@example.com"

    # 1. Kunjungan baru yang belum diperiksa: tagihan harus ditolak
    $vb = @{ doctor_id = 1; complaint = "Sakit kepala" } | ConvertTo-Json
    $baru = Invoke-RestMethod -Uri "$base/visits" -Method Post -ContentType "application/json" -Headers $pasien -Body $vb
    Write-Host "Kunjungan baru: $($baru.data.queue.queue_number) (belum diperiksa)"
    try {
        $tb = @{ visit_id = $baru.data.visit.id } | ConvertTo-Json
        Invoke-RestMethod -Uri "$base/payments" -Method Post -ContentType "application/json" -Headers $petugas -Body $tb
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Tagihan ditolak (pemeriksaan belum selesai), sudah benar." }

    # 2. Tagihan kunjungan 1 (konsultasi + Paracetamol x10 + Amoxicillin x15)
    Write-Host ""
    $body1 = @{ visit_id = 1 } | ConvertTo-Json
    $t1 = Invoke-RestMethod -Uri "$base/payments" -Method Post -ContentType "application/json" -Headers $petugas -Body $body1
    Write-Host "Tagihan 1: $($t1.data.payment_code) - $($t1.data.status)"
    Write-Host "  Konsultasi: $($t1.bill.consultation_fee)"
    $t1.bill.medicines | ForEach-Object { Write-Host "  $($_.medicine) x$($_.quantity) = $($_.subtotal)" }
    Write-Host "  TOTAL: $($t1.bill.total)"

    # 3. Buat tagihan yang sama lagi: harus ditolak
    try {
        Invoke-RestMethod -Uri "$base/payments" -Method Post -ContentType "application/json" -Headers $petugas -Body $body1
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Tagihan ganda ditolak, sudah benar." }

    # 4. Bayar tunai
    $b1 = @{ method = "tunai" } | ConvertTo-Json
    $p1 = Invoke-RestMethod -Uri "$base/payments/$($t1.data.id)/pay" -Method Patch -ContentType "application/json" -Headers $petugas -Body $b1
    Write-Host "Dibayar: $($p1.data.status) via $($p1.data.method)"

    # 5. Kunjungan 2, bayar QRIS
    Write-Host ""
    $body2 = @{ visit_id = 2 } | ConvertTo-Json
    $t2 = Invoke-RestMethod -Uri "$base/payments" -Method Post -ContentType "application/json" -Headers $petugas -Body $body2
    Write-Host "Tagihan 2: $($t2.data.payment_code) - TOTAL: $($t2.bill.total)"
    $b2 = @{ method = "qris" } | ConvertTo-Json
    $p2 = Invoke-RestMethod -Uri "$base/payments/$($t2.data.id)/pay" -Method Patch -ContentType "application/json" -Headers $petugas -Body $b2
    Write-Host "Dibayar: $($p2.data.status) via $($p2.data.method)"

    # 6. Pasien melihat bukti pembayaran miliknya
    Write-Host ""
    $mine = Invoke-RestMethod -Uri "$base/visits/my" -Method Get -Headers $pasien
    $v1 = $mine.data | Where-Object { $_.id -eq 1 }
    $bukti = Invoke-RestMethod -Uri "$base/payments/$($v1.payment.id)" -Method Get -Headers $pasien
    Write-Host "Bukti pembayaran pasien: $($bukti.data.payment_code) - $($bukti.data.status) - total $($bukti.bill.total)"

    # 7. Pasien tidak boleh melihat daftar semua pembayaran
    try {
        Invoke-RestMethod -Uri "$base/payments" -Method Get -Headers $pasien
        Write-Host "SALAH: pasien seharusnya ditolak"
    }
    catch { Write-Host "Pasien tidak boleh lihat daftar pembayaran (403), sudah benar." }

    # 8. Petugas melihat pembayaran lunas
    $lunas = Invoke-RestMethod -Uri "$base/payments?status=lunas" -Method Get -Headers $petugas
    Write-Host "Jumlah pembayaran lunas: $($lunas.data.Count)"
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}