@echo off
echo ============================================
echo   Building VIYGO Treatwell Scrapers
echo ============================================
echo.
echo [1/2] Building scraper.exe (v2 - per-kategori)...
go build -o scraper.exe scraper.go
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Build scraper.exe gagal.
    pause
    exit /b 1
)
echo [OK] scraper.exe siap.

echo.
echo [2/2] Building treatwell_scraper.exe (v1 - legacy)...
go build -o treatwell_scraper.exe treatwell_scraper.go
if %ERRORLEVEL% NEQ 0 (
    echo [WARN] Build treatwell_scraper.exe gagal (tidak fatal, v1 = legacy).
) else (
    echo [OK] treatwell_scraper.exe siap.
)

echo.
echo ============================================
echo   Build selesai!
echo.
echo   Pakai v2 (recommended):
echo     scraper.exe --kategori=hair
echo     scraper.exe --kategori=all
echo.
echo   Pakai v1 (legacy):
echo     treatwell_scraper.exe "https://www.treatwell.co.uk/places/..."
echo ============================================
pause
