# MaxSafe SVN Deployment Helper for WordPress.org
# Use this script AFTER your plugin is approved at https://wordpress.org/plugins/developers/add/

param(
    [string]$SvnUsername = "",
    [string]$WorkingDir = ".\svn-checkout"
)

$ErrorActionPreference = "Stop"
$root = $PSScriptRoot
$sourceSvn = Join-Path $root "wporg-svn"
$svnUrl = "https://plugins.svn.wordpress.org/maxsafe"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " MaxSafe WordPress.org SVN Deployment Tool " -ForegroundColor White
Write-Host "==========================================================" -ForegroundColor Cyan

# Check if svn command line tool is available
if (-not (Get-Command svn -ErrorAction SilentlyContinue)) {
    Write-Host "[WARNING] Subversion (svn) command line client was not found in your PATH." -ForegroundColor Yellow
    Write-Host "Please install TortoiseSVN (with CLI tools enabled) or SilkSVN, or use TortoiseSVN GUI." -ForegroundColor White
    Write-Host "`nAll pre-formatted SVN files are ready in:" -ForegroundColor Green
    Write-Host " -> $sourceSvn" -ForegroundColor Cyan
    exit 0
}

Write-Host "[1/4] Checking out remote SVN repository..." -ForegroundColor White
if (Test-Path $WorkingDir) {
    Remove-Item -Recurse -Force $WorkingDir
}

if ($SvnUsername -ne "") {
    svn checkout $svnUrl $WorkingDir --username $SvnUsername
} else {
    svn checkout $svnUrl $WorkingDir
}

Write-Host "[2/4] Syncing assets, trunk, tags, and branches..." -ForegroundColor White
# Copy assets
Copy-Item (Join-Path $sourceSvn "assets\*") (Join-Path $WorkingDir "assets") -Recurse -Force
# Copy trunk
Copy-Item (Join-Path $sourceSvn "trunk\*") (Join-Path $WorkingDir "trunk") -Recurse -Force
# Copy tags/1.2.0
$destTag = Join-Path $WorkingDir "tags\1.2.0"
if (-not (Test-Path $destTag)) {
    New-Item -ItemType Directory -Path $destTag -Force | Out-Null
}
Copy-Item (Join-Path $sourceSvn "tags\1.2.0\*") $destTag -Recurse -Force

Write-Host "[3/4] Adding new files to Subversion tracking..." -ForegroundColor White
Push-Location $WorkingDir
try {
    svn add --force * --auto-props=no
    Write-Host "[4/4] SVN status:" -ForegroundColor White
    svn status
    Write-Host "`nTo commit your changes to WordPress.org, run:" -ForegroundColor Green
    Write-Host "svn commit -m 'Release 1.1.0: Initial public release with complete visual assets' --username <your-wporg-username>" -ForegroundColor Yellow
} finally {
    Pop-Location
}

Write-Host "`nDeployment staging complete!" -ForegroundColor Green
