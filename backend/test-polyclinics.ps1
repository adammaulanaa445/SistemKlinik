try {
    Write-Host "GET /api/polyclinics"
    $polyclinics = Invoke-RestMethod -Uri "http://localhost:8000/api/polyclinics" -Method Get
    $polyclinics | ConvertTo-Json -Depth 5

    Write-Host ""
    Write-Host "GET /api/doctors"
    $doctors = Invoke-RestMethod -Uri "http://localhost:8000/api/doctors" -Method Get
    $doctors | ConvertTo-Json -Depth 5
}
catch {
    Write-Host "ERROR:"
    Write-Host $_.Exception.Message
}