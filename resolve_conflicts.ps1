$targetDir = "public/services/app-cap-frontend"

Get-ChildItem -Path $targetDir -Recurse -File | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -match '<<<<<<<.*?\n(.*?)\n=======\n.*?\n>>>>>>>.*?\n' -and $content -notmatch '<<<<<<<.*?\n.*?\n=======\n.*?\n<<<<<<<.*?\n') {
        $resolved = $content -replace '<<<<<<<.*?\n(.*?)\n=======\n.*?\n>>>>>>>.*?\n', '$1'
        Set-Content $_.FullName $resolved
        Write-Host "Resolved: $($_.FullName)"
    }
}

Write-Host "Conflict resolution completed."