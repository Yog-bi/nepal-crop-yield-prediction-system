param([string]$PhpPath = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$securePassword = Read-Host 'New unique administrator password (16–72 UTF-8 bytes)' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try {
    $plainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    $length = [Text.Encoding]::UTF8.GetByteCount($plainPassword)
    if ($length -lt 16 -or $length -gt 72) { throw 'Password must contain 16–72 UTF-8 bytes.' }
    $start = New-Object System.Diagnostics.ProcessStartInfo
    $start.FileName = $PhpPath
    $start.Arguments = '-r "echo password_hash(stream_get_contents(STDIN), PASSWORD_DEFAULT);"'
    $start.UseShellExecute = $false
    $start.CreateNoWindow = $true
    $start.RedirectStandardInput = $true
    $start.RedirectStandardOutput = $true
    $start.RedirectStandardError = $true
    # StandardInputEncoding is unavailable in this PowerShell environment.
    $process = [Diagnostics.Process]::Start($start)
    $process.StandardInput.Write($plainPassword)
    $process.StandardInput.Close()
    $hash = $process.StandardOutput.ReadToEnd()
    $process.WaitForExit()
    if ($process.ExitCode -ne 0 -or $hash -notmatch '^\$2y\$') { throw 'Hash generation failed.' }
    Set-Clipboard -Value $hash
    Write-Host 'Password hash copied privately to clipboard. Paste only into password_hash for the intended administrator. Clear the clipboard afterward.'
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    $plainPassword = $null
    $hash = $null
    $securePassword.Dispose()
}
