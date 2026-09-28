# 1. Login sebagai pasien Budi
$loginBody = @{
    email = "budi@example.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/login" -Method Post -ContentType "application/json" -Body $loginBody
    $token = $loginResponse.token
    Write-Host "Login OK, token didapat."

    # 2. Daftar kunjungan (asumsi dokter id 1 dari seeder)
    $visitBody = @{
        doctor_id = 1
        complaint = "Demam dan pusing sejak kemarin"
    } | ConvertTo-Json

    $visitResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/visits" -Method Post -ContentType "application/json" -Headers @{ Authorization = "Bearer $token" } -Body $visitBody
    Write-Host "VISIT SUCCESS:"
    $visitResponse | ConvertTo-Json -Depth 5
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}