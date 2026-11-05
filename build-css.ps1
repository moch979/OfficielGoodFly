$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$exe = Join-Path $root 'tools\tailwindcss.exe'
if (-not (Test-Path $exe)) {
    New-Item -ItemType Directory -Force (Join-Path $root 'tools') | Out-Null
    Invoke-WebRequest -Uri 'https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-windows-x64.exe' -OutFile $exe
}
Push-Location $root
& $exe -c tailwind.config.js -i assets/css/input.css -o assets/css/site.css --minify
Pop-Location
