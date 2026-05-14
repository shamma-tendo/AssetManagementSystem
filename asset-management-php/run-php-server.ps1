# Saves database to: asset-management-php\data\assets.sqlite
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $here
Write-Host "Open http://127.0.0.1:8080/ in your browser (PHP required)." -ForegroundColor Cyan
php -S 127.0.0.1:8080 -t public public/router.php
