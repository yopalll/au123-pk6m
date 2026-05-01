@echo off
echo ============================================
echo   Building Treatwell Scraper...
echo ============================================
go build -o treatwell_scraper.exe treatwell_scraper.go
if %ERRORLEVEL% == 0 (
    echo.
    echo [OK] Build sukses! treatwell_scraper.exe siap dipakai.
) else (
    echo.
    echo [ERROR] Build gagal. Pastikan Go sudah terinstall:
    echo   https://go.dev/dl/
)
echo ============================================
pause
