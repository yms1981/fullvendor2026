# FullVendor Laravel scheduler tick for Windows Task Scheduler.
# Recommended: run every 1 minute.
# Example action:
#   powershell.exe -NoProfile -ExecutionPolicy Bypass -File "H:\xampp\htdocs\mvc\api\scripts\cron\fv-schedule-run.ps1"

$ErrorActionPreference = 'Stop'

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ApiDir = Resolve-Path (Join-Path $ScriptDir '..\..')
$PhpBin = if ($env:FV_PHP_BIN) { $env:FV_PHP_BIN } else { 'php' }

# Prefer XAMPP php if available and FV_PHP_BIN not set
if (-not $env:FV_PHP_BIN) {
    foreach ($candidate in @('H:\xampp\php\php.exe', 'C:\xampp\php\php.exe')) {
        if (Test-Path $candidate) {
            $PhpBin = $candidate
            break
        }
    }
}

Set-Location $ApiDir
& $PhpBin artisan schedule:run --verbose --no-interaction
exit $LASTEXITCODE
