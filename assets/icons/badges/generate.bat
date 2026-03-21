for %%A in (Symbols\*.png) do (
  for %%B in (Plates\*.png) do (
    magick "%%B" "%%A" -gravity center -geometry +0-36 -composite "..\%%~nA%%~nB.png"
  )
)
for %%A in (Symbols\*.png) do (
  magick "Plates\bronze.png" "%%A" -gravity center -geometry +0-36 -composite -modulate 100,0,100 "..\%%~nAlocked.png"
)