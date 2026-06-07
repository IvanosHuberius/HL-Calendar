Add-Type -AssemblyName System.IO.Compression.FileSystem
$base = "F:\OneDrive\Webseiten\Ivans ChatGPT Joomla Erweiterungen\Claude.ai\Kalender"

Write-Host "=== com_calendar.zip (first 10 entries) ==="
$z = [System.IO.Compression.ZipFile]::OpenRead("$base\packages\com_calendar.zip")
$z.Entries | Select-Object -First 10 FullName | Format-Table -AutoSize
$z.Dispose()

Write-Host "=== mod_jwcalendar.zip (first 10 entries) ==="
$z = [System.IO.Compression.ZipFile]::OpenRead("$base\packages\mod_jwcalendar.zip")
$z.Entries | Select-Object -First 10 FullName | Format-Table -AutoSize
$z.Dispose()
