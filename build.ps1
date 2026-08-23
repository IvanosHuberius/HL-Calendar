$ErrorActionPreference = "Stop"
$base = "F:\OneDrive\Webseiten\Ivans ChatGPT Joomla Erweiterungen\Claude.ai\Kalender"

Add-Type -AssemblyName System.IO.Compression.FileSystem

function New-ZipWithForwardSlashes {
    param([string]$SourceDir, [string]$ZipPath, [string[]]$Include)

    if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }

    $zip = [System.IO.Compression.ZipFile]::Open($ZipPath, 'Create')

    foreach ($item in $Include) {
        $fullPath = Join-Path $SourceDir $item
        if (Test-Path $fullPath -PathType Container) {
            # It's a directory - add all files recursively
            Get-ChildItem $fullPath -Recurse -File | ForEach-Object {
                $relativePath = $_.FullName.Substring($SourceDir.Length + 1).Replace('\', '/')
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $relativePath) | Out-Null
            }
        } else {
            # It's a file
            $relativePath = $item.Replace('\', '/')
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $fullPath, $relativePath) | Out-Null
        }
    }

    $zip.Dispose()
}

# Clean old packages
Remove-Item -Force "$base\packages\*.zip" -ErrorAction SilentlyContinue
Remove-Item -Force "$base\pkg_jwcalendar_v1.9.1.zip" -ErrorAction SilentlyContinue

# Build com_calendar.zip
Write-Host "Building com_calendar.zip..."
New-ZipWithForwardSlashes -SourceDir "$base\temp\com_calendar" -ZipPath "$base\packages\com_calendar.zip" -Include @('administrator','media','site','calendar.xml')
Write-Host "  OK"

# Build mod_jwcalendar.zip
Write-Host "Building mod_jwcalendar.zip..."
New-ZipWithForwardSlashes -SourceDir "$base\temp\mod_jwcalendar" -ZipPath "$base\packages\mod_jwcalendar.zip" -Include @('language','mod_jwcalendar.php','mod_jwcalendar.xml','services','src','tmpl')
Write-Host "  OK"

# Build pkg_jwcalendar.zip
Write-Host "Building pkg_jwcalendar_v1.9.1.zip..."
New-ZipWithForwardSlashes -SourceDir $base -ZipPath "$base\pkg_jwcalendar_v1.9.1.zip" -Include @('pkg_jwcalendar.xml','packages','language')
Write-Host "  OK"

# Verify structure
Write-Host "`nVerifying ZIP structure..."
$zip = [System.IO.Compression.ZipFile]::OpenRead("$base\pkg_jwcalendar_v1.9.1.zip")
$zip.Entries | ForEach-Object { Write-Host "  $($_.FullName)" }
$zip.Dispose()

$fi = Get-Item "$base\pkg_jwcalendar_v1.9.1.zip"
Write-Host "`nDONE: $($fi.Name) ($([math]::Round($fi.Length/1024, 1)) KB)"
