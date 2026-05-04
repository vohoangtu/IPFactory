# WorldOS Docker Watchdog v5
# Monitors container health + application errors via docker compose logs --since

param(
    [string]$ComposeFile = "$PSScriptRoot/../deployment/docker-compose.prod.yml",
    [int]$Interval = 45
)

function Get-ContainerStatus {
    $lines = docker compose -f $ComposeFile ps --format "{{.Service}}|{{.Status}}|{{.Health}}" 2>$null
    $containers = @()
    foreach ($line in $lines) {
        if ($line -match '^(.+)\|(.+)\|(.*)$') {
            $containers += [PSCustomObject]@{
                Service = $Matches[1]
                Status  = $Matches[2].Trim()
                Health  = $Matches[3].Trim()
            }
        }
    }
    return $containers
}

function Test-ContainerHealthy($container) {
    if ($container.Status -match 'Exited|Dead|Restarting') { return $false }
    if ($container.Health -and $container.Health -ne 'healthy' -and $container.Health -ne '') { return $false }
    return $true
}

function Get-RecentErrors($service, $patterns) {
    # Only fetch logs from last 3 minutes to avoid scanning huge files
    $logs = docker compose -f $ComposeFile logs --since 3m --tail 50 $service 2>$null
    $matches = @()
    foreach ($pat in $patterns) {
        $count = ($logs | Select-String $pat).Count
        if ($count -gt 0) { $matches += "$pat(x$count)" }
    }
    return $matches -join ', '
}

function Restart-Service($service) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] RESTART: $service" -ForegroundColor Yellow
    docker compose -f $ComposeFile restart $service 2>$null | Out-Null
    Start-Sleep -Seconds 5
}

function Recreate-Service($service) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] RECREATE: $service" -ForegroundColor Magenta
    docker compose -f $ComposeFile up -d --no-deps --force-recreate $service 2>$null | Out-Null
    Start-Sleep -Seconds 10
}

Write-Host "Docker Watchdog v5 started (interval: ${Interval}s)" -ForegroundColor Cyan
Write-Host ""

$containerFails = @{}
$logErrorStreaks = @{}

while ($true) {
    $time = Get-Date -Format 'HH:mm:ss'
    $containers = Get-ContainerStatus
    $issues = @()

    # 1. Container health check
    foreach ($c in $containers) {
        if (-not (Test-ContainerHealthy $c)) {
            $issues += $c
            if (-not $containerFails.ContainsKey($c.Service)) { $containerFails[$c.Service] = 0 }
            $containerFails[$c.Service]++
        } else {
            $containerFails[$c.Service] = 0
        }
    }

    # 2. Application log checks (only last 3 minutes)
    $backendErr = Get-RecentErrors 'backend' @('"GET /index.php" 500', 'production.ERROR')
    $frontendErr = Get-RecentErrors 'frontend' @('Error', 'UnhandledPromiseRejection', 'Build error')
    $nginxErr = Get-RecentErrors 'nginx' @('" 500 ', '" 502 ', '" 503 ')
    $narrativeErr = Get-RecentErrors 'narrative_loom' @('ERROR', 'Exception', 'Traceback')

    $logIssues = @()
    if ($backendErr) { $logIssues += @{ Service = 'backend'; Errors = $backendErr } }
    if ($frontendErr) { $logIssues += @{ Service = 'frontend'; Errors = $frontendErr } }
    if ($nginxErr) { $logIssues += @{ Service = 'nginx'; Errors = $nginxErr } }
    if ($narrativeErr) { $logIssues += @{ Service = 'narrative_loom'; Errors = $narrativeErr } }

    foreach ($li in $logIssues) {
        $svc = $li.Service
        if (-not $logErrorStreaks.ContainsKey($svc)) { $logErrorStreaks[$svc] = 0 }
        $logErrorStreaks[$svc]++
    }
    # Reset streaks for services without current errors
    $allServices = @('backend', 'frontend', 'nginx', 'narrative_loom')
    foreach ($svc in $allServices) {
        $hasError = $logIssues | Where-Object { $_.Service -eq $svc }
        if (-not $hasError) { $logErrorStreaks[$svc] = 0 }
    }

    # Report
    $allOk = ($issues.Count -eq 0) -and ($logIssues.Count -eq 0)
    if ($allOk) {
        Write-Host "[$time] OK ($($containers.Count) containers)" -ForegroundColor Green
    }

    # Fix container issues
    foreach ($issue in $issues) {
        $fc = $containerFails[$issue.Service]
        Write-Host "[$time] CONTAINER: $($issue.Service) | $($issue.Status) | $($issue.Health) | #$fc" -ForegroundColor Red
        if ($fc -eq 1) { Restart-Service $issue.Service }
        elseif ($fc -ge 2) { Recreate-Service $issue.Service; $containerFails[$issue.Service] = 0 }
    }

    # Fix application log errors
    foreach ($li in $logIssues) {
        $svc = $li.Service
        $fc = $logErrorStreaks[$svc]
        Write-Host "[$time] LOGS: $svc | $($li.Errors) | streak #$fc" -ForegroundColor Red
        if ($fc -eq 2) {
            Restart-Service $svc
        } elseif ($fc -ge 4) {
            Recreate-Service $svc
            $logErrorStreaks[$svc] = 0
        }
    }

    Start-Sleep -Seconds $Interval
}
