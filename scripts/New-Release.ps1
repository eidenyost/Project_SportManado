param([Parameter(Mandatory=$true)][string]$Destination)
$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$destinationRoot = [IO.Path]::GetFullPath($Destination)
if ($destinationRoot.StartsWith($projectRoot.TrimEnd('\') + '\', [StringComparison]::OrdinalIgnoreCase) -or $destinationRoot -eq $projectRoot) {
    throw 'Choose a new destination outside the project.'
}
if (Test-Path -LiteralPath $destinationRoot) { throw 'Destination already exists; nothing was overwritten.' }
if (!(Test-Path -LiteralPath (Join-Path $projectRoot 'public\assets\app.css'))) { throw 'Run npm ci and npm run build first.' }
New-Item -ItemType Directory -Path $destinationRoot | Out-Null
foreach ($directory in @('app','bootstrap','config','routes','resources','public','scripts')) {
    & robocopy (Join-Path $projectRoot $directory) (Join-Path $destinationRoot $directory) /E /XJ /XD (Join-Path $projectRoot 'bootstrap\cache') /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -gt 7) { throw "Copy failed: $directory" }
}
foreach ($directory in @('database\migrations','database\factories','database\seeders')) {
    & robocopy (Join-Path $projectRoot $directory) (Join-Path $destinationRoot $directory) /E /XJ /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -gt 7) { throw "Copy failed: $directory" }
}
foreach ($directory in @('bootstrap\cache','storage\app\private','storage\app\public','storage\framework\cache\data','storage\framework\sessions','storage\framework\views','storage\logs')) {
    New-Item -ItemType Directory -Path (Join-Path $destinationRoot $directory) -Force | Out-Null
}
foreach ($file in @('artisan','composer.json','composer.lock','package.json','package-lock.json','tailwind.config.cjs','.env.hosting.example')) {
    Copy-Item -LiteralPath (Join-Path $projectRoot $file) -Destination (Join-Path $destinationRoot $file)
}
New-Item -ItemType Directory -Path (Join-Path $destinationRoot 'docs') -Force | Out-Null
foreach ($document in @('HOSTING_RUNBOOK.md','HOSTING_READINESS_RESULT.md','FUNCTIONAL_EVALUATION_20261029.md','FUNCTIONAL_SAFEGUARDS_UPDATE.md','PARTICIPANT_MANAGEMENT_UPDATE.md','SEARCH_FINANCE_NOTIFICATIONS_20261030.md','MANUAL_WITHDRAWALS_20261030.md')) {
    Copy-Item -LiteralPath (Join-Path $projectRoot "docs\$document") -Destination (Join-Path $destinationRoot "docs\$document")
}
$forbidden = Get-ChildItem -LiteralPath $destinationRoot -Recurse -Force -File | Where-Object { $_.Name -eq '.env' -or $_.Extension -in @('.sql','.sqlite') }
if ($forbidden) { throw 'Unexpected private environment or database file in release. Do not upload this directory.' }
Write-Output "Release source prepared: $destinationRoot"
Write-Output 'Dependencies, production .env, database and uploads must be provisioned separately on the server.'
