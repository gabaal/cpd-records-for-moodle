# Package the Moodle local plugin with the required cpdrecords/ ZIP root.
$ErrorActionPreference = 'Stop'

$tools = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = Split-Path -Parent $tools
$source = Join-Path $root 'cpdrecords'
$dist = Join-Path $root 'dist'
$zip = Join-Path $dist 'local_cpdrecords-0.1.0-beta.1.zip'

New-Item -ItemType Directory -Force -Path $dist | Out-Null

if (Test-Path $zip) {
    Remove-Item -Force $zip
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::Open($zip, 'Create')

try {
    Get-ChildItem $source -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($source.Length + 1).Replace('\', '/')
        $entry = "cpdrecords/$relative"
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $entry) | Out-Null
    }
} finally {
    $archive.Dispose()
}

Write-Output "Built $zip"
