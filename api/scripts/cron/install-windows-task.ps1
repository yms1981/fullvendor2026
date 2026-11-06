# Creates/updates Windows Task Scheduler entry for FullVendor Laravel schedule:run.
# Run once as Administrator:
#   powershell -ExecutionPolicy Bypass -File H:\xampp\htdocs\mvc\api\scripts\cron\install-windows-task.ps1

$ErrorActionPreference = 'Stop'

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$Runner = Join-Path $ScriptDir 'fv-schedule-run.ps1'
$TaskName = 'FullVendorLaravelScheduler'

if (-not (Test-Path $Runner)) {
    throw "Runner not found: $Runner"
}

$action = New-ScheduledTaskAction `
    -Execute 'powershell.exe' `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$Runner`""

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).Date -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -MultipleInstances IgnoreNew
$principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Highest

Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal | Out-Null

Write-Host "Scheduled task '$TaskName' installed. It runs every minute:"
Write-Host "  $Runner"
