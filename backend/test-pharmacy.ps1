$base = "http://localhost:8000/api"

function Login($email) {
    $body = @{ email = $email; password = "password123" } | ConvertTo-Json
    $r = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body $body
    return @{ Authorization = "Bearer $($r.token)" }
}

function Stok($headers, $id) {
    $m = Invoke-RestMethod -Uri "$base/medicines" -Method Get -Headers $headers
    return ($m.data | Where-Object { $_.id -eq $id }).stock
}

try {
    $farmasi = Login "farmasi@klinik.test"
    $dokter  = Login "dokter@klinik.test"

    # ===== Skenario 1: stok cukup =====
    Write-Host "== Skenario 1: stok cukup =="
    $list = Invoke-RestMethod -Uri "$base/prescriptions?status=menunggu" -Method Get -Headers $farmasi
    $resep1 = $list.data[0].id
    Write-Host "Resep masuk: $($list.data.Count) (resep #$resep1)"
    Write-Host "Stok Paracetamol sebelum: $(Stok $farmasi 1)"

    $p = Invoke-RestMethod -Uri "$base/prescriptions/$resep1/process" -Method Patch -Headers $farmasi
    Write-Host "Diproses -> $($p.data.status)"
    Write-Host "Stok Paracetamol sesudah: $(Stok $farmasi 1)"

    try {
        Invoke-RestMethod -Uri "$base/prescriptions/$resep1/process" -Method Patch -Headers $farmasi
        Write-Host "SALAH: proses ulang seharusnya ditolak"
    }
    catch { Write-Host "Proses ulang ditolak, sudah benar." }

    $c = Invoke-RestMethod -Uri "$base/prescriptions/$resep1/complete" -Method Patch -Headers $farmasi
    Write-Host "Diserahkan -> $($c.data.status)"

    # ===== Skenario 2: stok kurang =====
    Write-Host ""
    Write-Host "== Skenario 2: stok kurang =="
    $q = Invoke-RestMethod -Uri "$base/queues/today" -Method Get -Headers $dokter
    $a2 = $q.data | Where-Object { $_.queue_number -eq "A-002" } | Select-Object -First 1
    Invoke-RestMethod -Uri "$base/queues/$($a2.id)/call" -Method Patch -Headers $dokter | Out-Null
    Invoke-RestMethod -Uri "$base/queues/$($a2.id)/start" -Method Patch -Headers $dokter | Out-Null

    $body = @{
        visit_id           = $a2.visit_id
        examination_result = "Batuk berdahak 5 hari"
        diagnosis          = "Bronkitis ringan"
        prescription_items = @(
            @{ medicine_id = 4; quantity = 500; dosage = "1x sehari 1 tablet" }
        )
    } | ConvertTo-Json -Depth 5

    $rm = Invoke-RestMethod -Uri "$base/medical-records" -Method Post -ContentType "application/json" -Headers $dokter -Body $body
    $resep2 = $rm.data.prescription.id
    Write-Host "Resep baru #$resep2 (Vitamin C x500, stok $(Stok $farmasi 4))"

    try {
        Invoke-RestMethod -Uri "$base/prescriptions/$resep2/process" -Method Patch -Headers $farmasi
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch { Write-Host "Proses ditolak (stok tidak cukup), sudah benar." }
    Write-Host "Stok Vitamin C tetap: $(Stok $farmasi 4)"

    $h = Invoke-RestMethod -Uri "$base/prescriptions/$resep2/hold" -Method Patch -Headers $farmasi
    Write-Host "Ditandai -> $($h.data.status)"

    # Farmasi menambah stok, lalu proses ulang
    $restock = @{ stock = 600 } | ConvertTo-Json
    Invoke-RestMethod -Uri "$base/medicines/4/stock" -Method Patch -ContentType "application/json" -Headers $farmasi -Body $restock | Out-Null
    Write-Host "Stok Vitamin C ditambah -> $(Stok $farmasi 4)"

    $p2 = Invoke-RestMethod -Uri "$base/prescriptions/$resep2/process" -Method Patch -Headers $farmasi
    Write-Host "Diproses ulang -> $($p2.data.status)"
    Write-Host "Stok Vitamin C sesudah: $(Stok $farmasi 4)"

    $c2 = Invoke-RestMethod -Uri "$base/prescriptions/$resep2/complete" -Method Patch -Headers $farmasi
    Write-Host "Diserahkan -> $($c2.data.status)"

    # ===== Hak akses =====
    try {
        Invoke-RestMethod -Uri "$base/prescriptions" -Method Get -Headers $dokter
        Write-Host "SALAH: dokter seharusnya ditolak"
    }
    catch { Write-Host "Dokter tidak boleh akses resep (403), sudah benar." }
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}