$base = "http://localhost:8000/api"

function Login($email) {
    $body = @{ email = $email; password = "password123" } | ConvertTo-Json
    $r = Invoke-RestMethod -Uri "$base/login" -Method Post -ContentType "application/json" -Body $body
    return @{ Authorization = "Bearer $($r.token)" }
}

$pasien = Login "budi@example.com"
$dokter = Login "dokter@klinik.test"
$admin  = Login "admin@klinik.test"

# Buat kunjungan baru hari ini
$vb = @{ doctor_id = 1; complaint = "Cek dashboard" } | ConvertTo-Json
$v = Invoke-RestMethod -Uri "$base/visits" -Method Post -ContentType "application/json" -Headers $pasien -Body $vb
Write-Host "Kunjungan baru dibuat: $($v.data.queue.queue_number)"

# Cek dashboard dokter
$d = Invoke-RestMethod -Uri "$base/dashboard/doctor" -Method Get -Headers $dokter
Write-Host "Dashboard dokter - Total hari ini: $($d.data.total_hari_ini) | Menunggu: $($d.data.menunggu)"

# Cek dashboard admin
$a = Invoke-RestMethod -Uri "$base/dashboard/admin" -Method Get -Headers $admin
Write-Host "Dashboard admin - Kunjungan hari ini: $($a.data.kunjungan_hari_ini) | Antrian menunggu: $($a.data.antrian_menunggu)"