param (
    [Parameter(Mandatory = $false)]
    [string]$FilePath
)

[System.Net.ServicePointManager]::ServerCertificateValidationCallback = {$true}
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

# =========================================================================
# CONFIGURATION
# Fill in your InfinityFree FTP details here
# =========================================================================
$ftpHost = "ftpupload.net"    # e.g., ftp.epizy.com or ftpupload.net
$ftpUser = "if0_43109570"     # Your InfinityFree FTP Username
$ftpPass = "naksnaman12!!"    # Your InfinityFree vPanel/FTP Password
$remoteBaseFolder = "/htdocs" # The root folder on InfinityFree
# =========================================================================

if (-not $FilePath) {
    Write-Host "Usage: .\deploy.ps1 .\path\to\file.html" -ForegroundColor Yellow
    Write-Host "Please open this script in your editor and add your InfinityFree FTP credentials first." -ForegroundColor Cyan
    exit
}

$localBasePath = $PSScriptRoot
try {
    $localFilePath = (Resolve-Path $FilePath -ErrorAction Stop).Path
}
catch {
    Write-Host "Error: File '$FilePath' not found." -ForegroundColor Red
    exit
}

if (-not $localFilePath.StartsWith($localBasePath)) {
    Write-Host "Error: File must be inside the project folder." -ForegroundColor Red
    exit
}

$relativePath = $localFilePath.Substring($localBasePath.Length).Replace('\', '/')
$remoteUri = "ftp://$ftpHost$remoteBaseFolder$relativePath"

Write-Host "Uploading:" -ForegroundColor Cyan
Write-Host "  From: $localFilePath"
Write-Host "  To:   $remoteUri"

try {
    $ftp = [System.Net.FtpWebRequest]::Create($remoteUri)
    $ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $ftp.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $ftp.UsePassive = $true
    $ftp.KeepAlive = $false
    
    # If it fails, remove the '#' on the next line to enable TLS (sometimes required)
    $ftp.EnableSsl = $false

    $fileBytes = [System.IO.File]::ReadAllBytes($localFilePath)
    $requestStream = $ftp.GetRequestStream()
    $requestStream.Write($fileBytes, 0, $fileBytes.Length)
    $requestStream.Close()

    $response = $ftp.GetResponse()
    Write-Host "Success! $($response.StatusDescription)" -ForegroundColor Green
    $response.Close()
}
catch {
    Write-Host "Upload Failed: $_" -ForegroundColor Red
    Write-Host "Tip: Double check your host, username, and password in the script." -ForegroundColor Yellow
}
