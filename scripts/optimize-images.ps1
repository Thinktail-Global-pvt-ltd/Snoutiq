$ErrorActionPreference = "Stop"
Add-Type -AssemblyName System.Drawing

$jpegCodec = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() |
  Where-Object { $_.MimeType -eq "image/jpeg" }

$quality = 78L
$encoder = New-Object System.Drawing.Imaging.EncoderParameters(1)
$encoder.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter([System.Drawing.Imaging.Encoder]::Quality, $quality)

$targets = Get-ChildItem "src/assets" -Recurse -File | Where-Object {
  ($_.Extension -match "(?i)^\.(jpg|jpeg)$") -and
  ($_.Name -notmatch "(?i)\.__tmp\.jpg$") -and
  $_.Length -gt 100KB
}

foreach ($file in $targets) {
  $srcPath = $file.FullName
  $tmpPath = "$srcPath.__tmp.jpg"

  $img = $null
  $bmp = $null
  $gfx = $null

  try {
    $img = [System.Drawing.Image]::FromFile($srcPath)

    $maxW = 1600
    $maxH = 1600
    $scale = [Math]::Min(1.0, [Math]::Min($maxW / [double]$img.Width, $maxH / [double]$img.Height))
    $newW = [Math]::Max(1, [int][Math]::Round($img.Width * $scale))
    $newH = [Math]::Max(1, [int][Math]::Round($img.Height * $scale))

    if ($newW -ne $img.Width -or $newH -ne $img.Height) {
      $bmp = New-Object System.Drawing.Bitmap($newW, $newH)
      $gfx = [System.Drawing.Graphics]::FromImage($bmp)
      $gfx.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
      $gfx.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
      $gfx.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
      $gfx.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
      $gfx.DrawImage($img, 0, 0, $newW, $newH)
      $bmp.Save($tmpPath, $jpegCodec, $encoder)
    }
    else {
      $img.Save($tmpPath, $jpegCodec, $encoder)
    }
  }
  finally {
    if ($gfx) { $gfx.Dispose() }
    if ($bmp) { $bmp.Dispose() }
    if ($img) { $img.Dispose() }
  }

  Copy-Item -Path $tmpPath -Destination $srcPath -Force
  Remove-Item -Path $tmpPath -Force
}

Get-ChildItem "src/assets" -Recurse -File |
  Where-Object { $_.Extension -match "(?i)^\.(jpg|jpeg)$" } |
  Sort-Object Length -Descending |
  Select-Object -First 20 Name, Length
