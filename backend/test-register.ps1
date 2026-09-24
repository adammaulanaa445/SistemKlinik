$body = @{
    name = "Budi Santoso"
    email = "budi@example.com"
    password = "password123"
    password_confirmation = "password123"
    nik = "3578012345678901"
    gender = "L"
    birth_date = "1990-05-10"
    phone = "081234567890"
    address = "Jl. Merdeka No. 10, Surabaya"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/register" -Method Post -ContentType "application/json" -Body $body
    Write-Host "SUCCESS:"
    $response | ConvertTo-Json
}
catch {
    Write-Host "ERROR MESSAGE:"
    Write-Host $_.Exception.Message
    Write-Host ""
    Write-Host "ERROR DETAILS:"
    Write-Host $_.ErrorDetails.Message
}