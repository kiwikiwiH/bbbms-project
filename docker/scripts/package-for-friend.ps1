# Package Tarrlok for a friend (no PHP/Node/MySQL install on their PC).
# Run from project root:  .\docker\scripts\package-for-friend.ps1

$ErrorActionPreference = "Stop"
$Root = Resolve-Path (Join-Path $PSScriptRoot "..\..")
Set-Location $Root

$OutDir = Join-Path $Root "tarrlok-docker-share"
$TarPath = Join-Path $OutDir "tarrlok-images.tar"
$ZipPath = Join-Path $Root "tarrlok-docker-share.zip"

if (-not (Test-Path (Join-Path $Root "blockchain\node_modules"))) {
    Write-Host "Installing blockchain npm packages locally first..."
    Push-Location (Join-Path $Root "blockchain")
    npm ci
    if ($LASTEXITCODE -ne 0) { Pop-Location; exit 1 }
    npm run compile
    Pop-Location
}

if (-not (Test-Path (Join-Path $Root "tarrlok\vendor"))) {
    Write-Host "Installing Laravel composer packages locally (for dev only)..."
    Push-Location (Join-Path $Root "tarrlok")
    composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) { Pop-Location; exit 1 }
    Pop-Location
}

Write-Host "Building images (may take several minutes; needs internet for Composer)..."
docker compose build
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "ERROR: Docker build failed. Fix errors above, then run this script again." -ForegroundColor Red
    exit 1
}

Write-Host "Tagging images..."
docker tag bbbms-project-app tarrlok-app:demo
docker tag bbbms-project-blockchain tarrlok-blockchain:demo
docker pull mysql:8.0

if (Test-Path $OutDir) { Remove-Item -Recurse -Force $OutDir }
New-Item -ItemType Directory -Path $OutDir | Out-Null

Write-Host "Saving images to tarrlok-images.tar (large file)..."
docker save -o $TarPath tarrlok-app:demo tarrlok-blockchain:demo mysql:8.0
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Copy-Item (Join-Path $Root "docker-compose.share.yml") (Join-Path $OutDir "docker-compose.yml")
Copy-Item (Join-Path $Root "docker\.env.example") (Join-Path $OutDir ".env")
Copy-Item (Join-Path $Root "docker\README-FOR-FRIEND.md") (Join-Path $OutDir "README.md")

if (Test-Path $ZipPath) { Remove-Item -Force $ZipPath }
Compress-Archive -Path (Join-Path $OutDir "*") -DestinationPath $ZipPath

if (-not (Test-Path $ZipPath)) {
    Write-Host "ERROR: Zip was not created at $ZipPath" -ForegroundColor Red
    exit 1
}

$zipMb = [math]::Round((Get-Item $ZipPath).Length / 1MB, 1)
Write-Host ""
Write-Host "SUCCESS. Share this file:" -ForegroundColor Green
Write-Host "  $ZipPath"
Write-Host "  ($zipMb MB, includes Docker images)"
Write-Host ""
Write-Host "Friend steps:"
Write-Host "  1. Install Docker Desktop"
Write-Host "  2. Unzip tarrlok-docker-share.zip"
Write-Host "  3. docker load -i tarrlok-images.tar"
Write-Host "  4. docker compose up"
Write-Host "  5. Open http://localhost:8080"
