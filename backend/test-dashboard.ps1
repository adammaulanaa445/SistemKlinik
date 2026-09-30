$base = "http://localhost:8000/api"

function Login($email) {
    $body = @{ email = $email; password = "password123" } | ConvertTo-Json
    $r = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body $body
    return @{ Authorization = "Bearer $($r.token)" }
}

try {
    $dokter  = Login "dokter@klinik.test"
    $farmasi = Login "farmasi@klinik.test"
    $admin   = Login "admin@klinik.test"
    $pasien  = Login "budi@example.com"

    Write-Host "== Dashboard Dokter =="
    $d = Invoke-RestMethod -Uri "$base/dashboard/doctor" -Method Get -Headers $dokter
    Write-Host "Total hari ini: $($d.data.total_hari_ini) | Menunggu: $($d.data.menunggu) | Selesai: $($d.data.selesai)"

    Write-Host ""
    Write-Host "== Dashboard Farmasi =="
    $f = Invoke-RestMethod -Uri "$base/dashboard/pharmacy" -Method Get -Headers $farmasi
    Write-Host "Menunggu: $($f.data.menunggu) | Diproses: $($f.data.diproses) | Tertunda: $($f.data.tertunda) | Selesai: $($f.data.selesai)"
    Write-Host "Obat stok menipis: $($f.data.stok_menipis.Count)"

    Write-Host ""
    Write-Host "== Dashboard Admin =="
    $a = Invoke-RestMethod -Uri "$base/dashboard/admin" -Method Get -Headers $admin
    Write-Host "Total pasien: $($a.data.total_pasien) | Total dokter: $($a.data.total_dokter)"
    Write-Host "Kunjungan hari ini: $($a.data.kunjungan_hari_ini) | Antrian menunggu: $($a.data.antrian_menunggu)"
    Write-Host "Pendapatan hari ini: $($a.data.pendapatan_hari_ini)"
    $a.data.kunjungan_per_poli_hari_ini | ForEach-Object { Write-Host "  $($_.poli): $($_.total) kunjungan" }

    Write-Host ""
    Write-Host "== Hak akses =="
    try {
        Invoke-RestMethod -Uri "$base/dashboard/admin" -Method Get -Headers $pasien
        Write-Host "SALAH: pasien seharusnya ditolak"
    }
    catch { Write-Host "Pasien tidak boleh akses dashboard admin (403), sudah benar." }

    try {
        Invoke-RestMethod -Uri "$base/dashboard/doctor" -Method Get -Headers $farmasi
        Write-Host "SALAH: farmasi seharusnya ditolak"
    }
    catch { Write-Host "Farmasi tidak boleh akses dashboard dokter (403), sudah benar." }
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}