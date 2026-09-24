$body = @{
    email = "budi@example.com"
    password = "password123"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/login" -Method Post -ContentType "application/json" -Body $body
    Write-Host "LOGIN SUCCESS:"
    $response | ConvertTo-Json
    
    # Simpan token buat tes /me
    $token = $response.token
    Write-Host ""
    Write-Host "Testing /api/me with token..."
    $meResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/me" -Method Get -Headers @{ Authorization = "Bearer $token" }
    Write-Host "ME SUCCESS:"
    $meResponse | ConvertTo-Json
}
catch {
    Write-Host "ERROR MESSAGE:"
    Write-Host $_.Exception.Message
}