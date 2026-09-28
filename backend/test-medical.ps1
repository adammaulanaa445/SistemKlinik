$base = "http://localhost:8000/api"

function Login($email) {
    $body = @{ email = $email; password = "password123" } | ConvertTo-Json
    $r = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body $body
    return @{ Authorization = "Bearer $($r.token)" }
}

try {
    $dokter = Login "dokter@klinik.test"

    # Cari antrian yang sedang diproses (A-001)
    $list = Invoke-RestMethod -Uri "$base/queues/today" -Method Get -Headers $dokter
    $aktif = $list.data | Where-Object { $_.status -eq "diproses" } | Select-Object -First 1
    Write-Host "Memeriksa pasien antrian: $($aktif.queue_number)"

    # Simpan rekam medis + resep 2 obat
    $body = @{
        visit_id           = $aktif.visit_id
        examination_result = "Suhu 38.5C, tenggorokan merah"
        diagnosis          = "Infeksi saluran pernapasan atas"
        treatment          = "Istirahat dan obat"
        notes              = "Kontrol jika demam berlanjut 3 hari"
        prescription_items = @(
            @{ medicine_id = 1; quantity = 10; dosage = "3x sehari 1 tablet" },
            @{ medicine_id = 2; quantity = 15; dosage = "3x sehari 1 kapsul" }
        )
    } | ConvertTo-Json -Depth 5

    $r = Invoke-RestMethod -Uri "$base/medical-records" -Method Post -ContentType "application/json" -Headers $dokter -Body $body
    Write-Host "Rekam medis: $($r.message)"
    Write-Host "Status resep: $($r.data.prescription.status)"
    $r.data.prescription.items | ForEach-Object { Write-Host "  $($_.medicine.name) x$($_.quantity) - $($_.dosage)" }

    # Cek status antrian sekarang
    $list2 = Invoke-RestMethod -Uri "$base/queues/today" -Method Get -Headers $dokter
    $list2.data | ForEach-Object { Write-Host "Antrian $($_.queue_number): $($_.status)" }

    # Coba simpan lagi (harus ditolak)
    try {
        Invoke-RestMethod -Uri "$base/medical-records" -Method Post -ContentType "application/json" -Headers $dokter -Body $body
        Write-Host "SALAH: seharusnya ditolak"
    }
    catch {
        Write-Host "Simpan ulang ditolak, sudah benar."
    }

    # Riwayat medis pasien 1
    $h = Invoke-RestMethod -Uri "$base/patients/1/medical-records" -Method Get -Headers $dokter
    Write-Host "Jumlah riwayat medis pasien 1: $($h.data.Count)"
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}